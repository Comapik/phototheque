<?php

namespace App\Controller\Admin;

use App\Entity\AccesClient;
use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ClientController extends AbstractController
{
    #[Route('/admin/clients', name: 'admin_clients', methods: ['GET'])]
    public function index(ClientRepository $clientRepository): Response
    {
        return $this->render('admin/client/index.html.twig', [
            'clients' => $clientRepository->findAll(),
        ]);
    }

    #[Route('/admin/clients/nouveau', name: 'admin_client_nouveau', methods: ['GET', 'POST'])]
    public function nouveau(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client->setPassword($hasher->hashPassword($client, $form->get('codeAcces')->getData()));
            $em->persist($client);

            foreach ($form->get('evenements')->getData() as $evenement) {
                $acces = new AccesClient();
                $acces->setClient($client);
                $acces->setEvenement($evenement);
                $em->persist($acces);
            }

            $em->flush();

            $this->addFlash('success', 'Client créé.');

            return $this->redirectToRoute('admin_accueil');
        }

        return $this->render('admin/client/form.html.twig', [
            'form' => $form,
            'client' => $client,
        ]);
    }

    #[Route('/admin/clients/{id}/edition', name: 'admin_client_edition', methods: ['GET', 'POST'])]
    public function edition(Client $client, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(ClientType::class, $client, ['code_requis' => false]);
        $form->get('evenements')->setData($client->getEvenements());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nouveauCode = $form->get('codeAcces')->getData();
            if ($nouveauCode) {
                $client->setPassword($hasher->hashPassword($client, $nouveauCode));
            }

            $selectionnes = $form->get('evenements')->getData();
            $selectionnesIds = array_map(static fn ($evenement) => $evenement->getId(), $selectionnes);

            foreach ($client->getAccesClients() as $acces) {
                if (!in_array($acces->getEvenement()->getId(), $selectionnesIds, true)) {
                    $client->removeAccesClient($acces);
                    $em->remove($acces);
                }
            }

            $dejaLies = array_map(static fn ($evenement) => $evenement->getId(), $client->getEvenements());
            foreach ($selectionnes as $evenement) {
                if (!in_array($evenement->getId(), $dejaLies, true)) {
                    $acces = new AccesClient();
                    $acces->setClient($client);
                    $acces->setEvenement($evenement);
                    $em->persist($acces);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Client mis à jour.');

            return $this->redirectToRoute('admin_clients');
        }

        return $this->render('admin/client/form.html.twig', [
            'form' => $form,
            'client' => $client,
        ]);
    }

    #[Route('/admin/clients/{id}/suppression', name: 'admin_client_suppression', methods: ['POST'])]
    public function suppression(Client $client, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('suppression'.$client->getId(), $request->request->get('_token'))) {
            $em->remove($client);
            $em->flush();
            $this->addFlash('success', 'Client supprimé.');
        }

        return $this->redirectToRoute('admin_clients');
    }
}
