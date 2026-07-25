<?php

namespace App\Controller\Admin;

use App\Entity\AccesClient;
use App\Entity\Evenement;
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
            if (!$this->isCsrfTokenValid('submit', $request->request->get('_csrf_token'))) {
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
                    $photo = $imageProcessor->traiter($fichier, $evenement, $ordre);
                    $em->persist($photo);
                    ++$ordre;
                    ++$nbImportees;
                } catch (\InvalidArgumentException $e) {
                    $erreurs[] = $e->getMessage();
                }
            }

            $em->flush();

            if ($nbImportees > 0) {
                $this->addFlash('success', sprintf('%d photo(s) importée(s).', $nbImportees));
            }
            foreach ($erreurs as $erreur) {
                $this->addFlash('erreur', $erreur);
            }

            return $this->redirectToRoute('admin_evenement_photos', ['id' => $evenement->getId()]);
        }

        return $this->render('admin/evenement/photos.html.twig', [
            'evenement' => $evenement,
            'photos' => $photoRepository->findBy(['evenement' => $evenement], ['ordre' => 'ASC']),
        ]);
    }
}
