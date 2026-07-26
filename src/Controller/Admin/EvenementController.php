<?php

namespace App\Controller\Admin;

use App\Entity\AccesClient;
use App\Entity\Evenement;
use App\Entity\Photo;
use App\Form\EvenementType;
use App\Repository\PhotoRepository;
use App\Service\ImageProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
class EvenementController extends AbstractController
{
    #[Route('/admin/evenements/nouveau', name: 'admin_evenement_nouveau', methods: ['GET', 'POST'])]
    public function nouveau(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$evenement->getSlug()) {
                $evenement->setSlug(strtolower((string) $slugger->slug($evenement->getNom())));
            }

            $em->persist($evenement);

            foreach ($form->get('clients')->getData() as $client) {
                $acces = new AccesClient();
                $acces->setClient($client);
                $acces->setEvenement($evenement);
                $em->persist($acces);
            }

            $em->flush();

            $this->addFlash('success', 'Événement créé.');

            return $this->redirectToRoute('admin_evenement_photos', ['id' => $evenement->getId()]);
        }

        return $this->render('admin/evenement/form.html.twig', [
            'form' => $form,
            'evenement' => $evenement,
        ]);
    }

    #[Route('/admin/evenements/{id}/edition', name: 'admin_evenement_edition', methods: ['GET', 'POST'])]
    public function edition(Evenement $evenement, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->get('clients')->setData($evenement->getClients());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$evenement->getSlug()) {
                $evenement->setSlug(strtolower((string) $slugger->slug($evenement->getNom())));
            }

            $selectionnes = $form->get('clients')->getData();
            $selectionnesIds = array_map(static fn ($client) => $client->getId(), $selectionnes);

            foreach ($evenement->getAccesClients() as $acces) {
                if (!in_array($acces->getClient()->getId(), $selectionnesIds, true)) {
                    $evenement->removeAccesClient($acces);
                    $em->remove($acces);
                }
            }

            $dejaLies = array_map(static fn ($client) => $client->getId(), $evenement->getClients());
            foreach ($selectionnes as $client) {
                if (!in_array($client->getId(), $dejaLies, true)) {
                    $acces = new AccesClient();
                    $acces->setClient($client);
                    $acces->setEvenement($evenement);
                    $em->persist($acces);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Événement mis à jour.');

            return $this->redirectToRoute('admin_accueil');
        }

        return $this->render('admin/evenement/form.html.twig', [
            'form' => $form,
            'evenement' => $evenement,
        ]);
    }

    #[Route('/admin/evenements/{id}/photos', name: 'admin_evenement_photos', methods: ['GET', 'POST'])]
    public function photos(
        Evenement $evenement,
        Request $request,
        EntityManagerInterface $em,
        PhotoRepository $photoRepository,
        ImageProcessor $imageProcessor,
    ): Response {
        if ($request->isMethod('POST')) {
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

                return $this->redirectToRoute('admin_evenement_photos', ['id' => $evenement->getId()]);
            }

            if (!$this->isCsrfTokenValid('submit', $request->request->get('_csrf_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $fichiers = $request->files->get('photos', []);
            $ordre = $photoRepository->prochainOrdre($evenement);
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
                    $photo = $imageProcessor->traiter($fichier, $evenement, $ordre);
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
                $this->addFlash('success', sprintf('%d photo(s) importée(s).', $nbImportees));
            }
            foreach ($erreurs as $erreur) {
                $this->addFlash('erreur', $erreur);
            }

            return $this->redirectToRoute('admin_evenement_photos', ['id' => $evenement->getId()]);
        }

        $photos = $photoRepository->findBy(['evenement' => $evenement], ['ordre' => 'ASC']);

        return $this->render('admin/evenement/photos.html.twig', [
            'evenement' => $evenement,
            'photosPhotographe' => array_filter($photos, static fn (Photo $photo): bool => !$photo->estAjouteeParClient()),
            'photosClients' => array_filter($photos, static fn (Photo $photo): bool => $photo->estAjouteeParClient()),
        ]);
    }

    #[Route('/admin/evenements/{id}/suppression', name: 'admin_evenement_suppression', methods: ['POST'])]
    public function suppression(Evenement $evenement, Request $request, EntityManagerInterface $em, ImageProcessor $imageProcessor): Response
    {
        if (!$this->isCsrfTokenValid('suppression_evenement'.$evenement->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $nom = $evenement->getNom();

        foreach ($evenement->getPhotos() as $photo) {
            $imageProcessor->supprimerFichiers($photo);
        }

        // Les photos et accès clients liés sont supprimés en cascade (cf. Evenement).
        $em->remove($evenement);
        $em->flush();

        $this->addFlash('success', sprintf('Événement "%s" et ses photos supprimés.', $nom));

        return $this->redirectToRoute('admin_accueil');
    }
}
