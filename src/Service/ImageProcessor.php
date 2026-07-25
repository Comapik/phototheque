<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Entity\Photo;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Génère les versions basse définition et miniature d'une photo uploadée,
 * et les stocke hors de public/ (cf. CLAUDE.md §6). Utilise GD : Imagick
 * n'est disponible sur aucune version PHP de l'environnement MAMP local.
 *
 * Le fichier HD original transite par le serveur (upload PHP temporaire)
 * mais n'est jamais écrit sur disque de façon persistante.
 */
class ImageProcessor
{
    private const LARGEUR_MAX_BASSE_DEF = 2000;
    private const LARGEUR_MAX_MINIATURE = 400;
    private const QUALITE_JPEG = 80;

    public function __construct(
        #[Autowire('%app.photos_dir%')]
        private readonly string $photosDir,
    ) {
    }

    public function traiter(UploadedFile $fichier, Evenement $evenement, int $ordre): Photo
    {
        $nomOriginal = basename($fichier->getClientOriginalName());

        $info = getimagesize($fichier->getPathname());
        if (false === $info) {
            throw new \InvalidArgumentException(sprintf('"%s" n\'est pas une image valide.', $nomOriginal));
        }

        [$largeurOrigine, $hauteurOrigine, $type] = $info;

        $source = match ($type) {
            \IMAGETYPE_JPEG => imagecreatefromjpeg($fichier->getPathname()),
            \IMAGETYPE_PNG => imagecreatefrompng($fichier->getPathname()),
            default => throw new \InvalidArgumentException(sprintf(
                '"%s" : format non supporté (seuls JPEG et PNG sont acceptés).',
                $nomOriginal
            )),
        };

        if (\IMAGETYPE_JPEG === $type) {
            [$source, $largeurOrigine, $hauteurOrigine] = $this->corrigerOrientation(
                $source,
                $fichier->getPathname(),
                $largeurOrigine,
                $hauteurOrigine
            );
        }

        $dossierEvenement = $this->photosDir.'/'.$evenement->getId();
        $dossierBasseDef = $dossierEvenement.'/basse-def';
        $dossierMiniature = $dossierEvenement.'/miniatures';

        foreach ([$dossierBasseDef, $dossierMiniature] as $dossier) {
            if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
                throw new \RuntimeException(sprintf('Impossible de créer le dossier "%s".', $dossier));
            }
        }

        $cheminBasseDefAbsolu = $dossierBasseDef.'/'.$nomOriginal;
        $cheminMiniatureAbsolu = $dossierMiniature.'/'.$nomOriginal;

        [$imageBasseDef, $largeur, $hauteur] = $this->redimensionner(
            $source,
            $largeurOrigine,
            $hauteurOrigine,
            self::LARGEUR_MAX_BASSE_DEF
        );
        imagejpeg($imageBasseDef, $cheminBasseDefAbsolu, self::QUALITE_JPEG);
        imagedestroy($imageBasseDef);

        [$imageMiniature] = $this->redimensionner(
            $source,
            $largeurOrigine,
            $hauteurOrigine,
            self::LARGEUR_MAX_MINIATURE
        );
        imagejpeg($imageMiniature, $cheminMiniatureAbsolu, self::QUALITE_JPEG);
        imagedestroy($imageMiniature);

        imagedestroy($source);

        $photo = new Photo();
        $photo->setEvenement($evenement);
        $photo->setNomOriginal($nomOriginal);
        $photo->setCheminBasseDef($evenement->getId().'/basse-def/'.$nomOriginal);
        $photo->setCheminMiniature($evenement->getId().'/miniatures/'.$nomOriginal);
        $photo->setLargeur($largeur);
        $photo->setHauteur($hauteur);
        $photo->setTaille((int) filesize($cheminBasseDefAbsolu));
        $photo->setOrdre($ordre);

        return $photo;
    }

    public function supprimerFichiers(Photo $photo): void
    {
        @unlink($this->photosDir.'/'.$photo->getCheminBasseDef());
        @unlink($this->photosDir.'/'.$photo->getCheminMiniature());
    }

    /**
     * @return array{0: \GdImage, 1: int, 2: int}
     */
    private function redimensionner(\GdImage $source, int $largeurOrigine, int $hauteurOrigine, int $coteMax): array
    {
        $ratio = min(1, $coteMax / max($largeurOrigine, $hauteurOrigine));
        $largeur = max(1, (int) round($largeurOrigine * $ratio));
        $hauteur = max(1, (int) round($hauteurOrigine * $ratio));

        $destination = imagecreatetruecolor($largeur, $hauteur);
        // Fond blanc : aplatit une éventuelle transparence PNG avant export en JPEG.
        $blanc = imagecolorallocate($destination, 255, 255, 255);
        imagefill($destination, 0, 0, $blanc);

        imagecopyresampled($destination, $source, 0, 0, 0, 0, $largeur, $hauteur, $largeurOrigine, $hauteurOrigine);

        return [$destination, $largeur, $hauteur];
    }

    /**
     * @return array{0: \GdImage, 1: int, 2: int}
     */
    private function corrigerOrientation(\GdImage $image, string $chemin, int $largeur, int $hauteur): array
    {
        $exif = @exif_read_data($chemin);
        $orientation = $exif['Orientation'] ?? 1;

        $image = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if (\in_array($orientation, [6, 8], true)) {
            [$largeur, $hauteur] = [$hauteur, $largeur];
        }

        return [$image, $largeur, $hauteur];
    }
}
