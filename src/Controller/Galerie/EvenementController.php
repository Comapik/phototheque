<?php

namespace App\Controller\Galerie;

use App\Entity\Client;
use App\Entity\Evenement;
use App\Entity\Photo;
use App\Repository\EvenementRepository;
use App\Repository\PhotoRepository;
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
    public function voir(Evenement $evenement, PhotoRepository $photoRepository): Response
    {
        $photos = $photoRepository->findBy(['evenement' => $evenement], ['ordre' => 'ASC']);

        return $this->render('galerie/evenement.html.twig', [
            'evenement' => $evenement,
            'photosPhotographe' => array_filter($photos, static fn (Photo $photo): bool => !$photo->estAjouteeParClient()),
            'photosClients' => array_filter($photos, static fn (Photo $photo): bool => $photo->estAjouteeParClient()),
        ]);
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
        if (!$this->isCsrfTokenValid('upload', $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $fichiers = $request->files->get('photos', []);
        $ordre = $photoRepository->prochainOrdre($evenement);
        $nbImportees = 0;
        $erreurs = [];

        foreach ($fichiers as $fichier) {
            if (!$fichier instanceof UploadedFile || !$fichier->isValid()) {
                continue;
            }

            try {
                $photo = $imageProcessor->traiter($fichier, $evenement, $ordre, Photo::ORIGINE_CLIENT);
                $em->persist($photo);
                ++$ordre;
                ++$nbImportees;
            } catch (\InvalidArgumentException $e) {
                $erreurs[] = $e->getMessage();
            }
        }

        $em->flush();

        if ($nbImportees > 0) {
            $this->addFlash('success', sprintf('%d photo(s) ajoutée(s). Merci !', $nbImportees));
        }
        foreach ($erreurs as $erreur) {
            $this->addFlash('erreur', $erreur);
        }

        return $this->redirectToRoute('client_evenement_voir', ['id' => $evenement->getId()]);
    }
}
