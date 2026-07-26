<?php

namespace App\Controller\Galerie;

use App\Entity\Client;
use App\Entity\Evenement;
use App\Entity\Photo;
use App\Repository\EvenementRepository;
use App\Repository\PhotoRepository;
use App\Security\PrenomSession;
use App\Service\ArchivePhotos;
use App\Service\ImageProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EvenementController extends AbstractController
{
    #[Route('/mes-evenements', name: 'client_mes_evenements', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function mesEvenements(EvenementRepository $evenementRepository): Response
    {
        /** @var Client $client */
        $client = $this->getUser();

        return $this->render('galerie/mes_evenements.html.twig', [
            'evenements' => $evenementRepository->findAccessiblesPour($client),
            'client' => $client,
        ]);
    }

    #[Route('/evenements/{id}', name: 'client_evenement_voir', methods: ['GET'])]
    #[IsGranted('EVENEMENT_VOIR', subject: 'evenement')]
    public function voir(Evenement $evenement, PhotoRepository $photoRepository, Request $request): Response
    {
        $photos = $photoRepository->findBy(['evenement' => $evenement], ['ordre' => 'ASC']);

        return $this->render('galerie/evenement.html.twig', [
            'evenement' => $evenement,
            'photosPhotographe' => array_filter($photos, static fn (Photo $photo): bool => !$photo->estAjouteeParClient()),
            'photosClients' => array_filter($photos, static fn (Photo $photo): bool => $photo->estAjouteeParClient()),
            'ongletActif' => 'invites' === $request->query->get('onglet') ? 'invites' : 'photographe',
        ]);
    }

    #[Route('/evenements/{id}/telechargement-groupe', name: 'client_photos_telechargement_groupe', methods: ['POST'])]
    #[IsGranted('EVENEMENT_VOIR', subject: 'evenement')]
    public function telechargementGroupe(
        Evenement $evenement,
        Request $request,
        PhotoRepository $photoRepository,
        ArchivePhotos $archivePhotos,
    ): Response {
        $ids = array_map('intval', $request->request->all('photos'));

        if ([] === $ids) {
            $this->addFlash('erreur', 'Aucune photo sélectionnée.');

            return $this->redirectToRoute('client_evenement_voir', ['id' => $evenement->getId()]);
        }

        $photos = $photoRepository->findBy(['id' => $ids, 'evenement' => $evenement]);

        if ([] === $photos) {
            throw $this->createNotFoundException();
        }

        $chemin = $archivePhotos->creerZip($photos);

        return $this->file($chemin, sprintf('%s-photos.zip', $evenement->getSlug()))->deleteFileAfterSend();
    }

    #[Route('/evenements/{id}/upload', name: 'client_evenement_upload', methods: ['POST'])]
    #[IsGranted('EVENEMENT_UPLOAD', subject: 'evenement')]
    public function upload(
        Evenement $evenement,
        Request $request,
        EntityManagerInterface $em,
        PhotoRepository $photoRepository,
        ImageProcessor $imageProcessor,
    ): Response {
        // Un gros volume de photos est envoyé par le navigateur en plusieurs
        // lots successifs (cf. redimensionnement_controller.js) : seul le
        // dernier lot est une vraie soumission de formulaire (redirection,
        // messages). Les lots intermédiaires reçoivent une réponse légère.
        $estLotIntermediaire = 'intermediaire' === $request->headers->get('X-Upload-Lot');

        // Si le serveur a rejeté la requête car elle dépasse post_max_size, PHP vide
        // silencieusement $_POST et $_FILES : le jeton CSRF semble alors "invalide"
        // alors que le vrai problème est la taille des photos envoyées.
        if (0 === $request->request->count() && 0 === $request->files->count() && $request->headers->get('Content-Length') > 0) {
            $message = sprintf(
                'Les photos envoyées sont trop volumineuses pour le serveur (limite actuelle : %s). Réessayez avec moins de photos à la fois.',
                ini_get('post_max_size')
            );

            if ($estLotIntermediaire) {
                return $this->json(['importees' => 0, 'erreurs' => [$message]], 413);
            }

            $this->addFlash('erreur', $message);

            return $this->redirectToRoute('client_evenement_voir', ['id' => $evenement->getId(), 'onglet' => 'invites']);
        }

        if (!$this->isCsrfTokenValid('upload', $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $fichiers = $request->files->get('photos', []);
        $ordre = $photoRepository->prochainOrdre($evenement);
        $prenomUploadeur = PrenomSession::get($request);
        $nbImportees = 0;
        $erreurs = [];

        foreach ($fichiers as $fichier) {
            if (!$fichier instanceof UploadedFile) {
                continue;
            }

            if (!$fichier->isValid()) {
                $erreurs[] = sprintf('"%s" n\'a pas pu être envoyée (%s).', $fichier->getClientOriginalName(), $fichier->getErrorMessage());
                continue;
            }

            try {
                $photo = $imageProcessor->traiter($fichier, $evenement, $ordre, Photo::ORIGINE_CLIENT);
                $photo->setPrenomUploadeur($prenomUploadeur);
                $em->persist($photo);
                ++$ordre;
                ++$nbImportees;
            } catch (\InvalidArgumentException $e) {
                $erreurs[] = $e->getMessage();
            }
        }

        $em->flush();

        if ($estLotIntermediaire) {
            return $this->json(['importees' => $nbImportees, 'erreurs' => $erreurs]);
        }

        if ($nbImportees > 0) {
            $this->addFlash('success', sprintf('%d photo(s) ajoutée(s). Merci !', $nbImportees));
        }
        foreach ($erreurs as $erreur) {
            $this->addFlash('erreur', $erreur);
        }

        return $this->redirectToRoute('client_evenement_voir', ['id' => $evenement->getId(), 'onglet' => 'invites']);
    }
}
