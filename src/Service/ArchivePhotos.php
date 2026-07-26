<?php

namespace App\Service;

use App\Entity\Photo;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Regroupe plusieurs photos (basse-def) dans une archive ZIP temporaire,
 * pour le téléchargement groupé (admin et client).
 */
class ArchivePhotos
{
    public function __construct(
        #[Autowire('%app.photos_dir%')]
        private readonly string $photosDir,
    ) {
    }

    /**
     * @param Photo[] $photos
     *
     * @return string Chemin du fichier ZIP temporaire (à supprimer après envoi)
     */
    public function creerZip(array $photos): string
    {
        $chemin = tempnam(sys_get_temp_dir(), 'photos_');

        $zip = new \ZipArchive();
        $zip->open($chemin, \ZipArchive::OVERWRITE);

        foreach ($photos as $photo) {
            $zip->addFile($this->photosDir.'/'.$photo->getCheminBasseDef(), $photo->getNomOriginal());
        }

        $zip->close();

        return $chemin;
    }
}
