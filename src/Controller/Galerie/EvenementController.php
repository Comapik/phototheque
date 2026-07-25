<?php

namespace App\Controller\Galerie;

use App\Entity\Client;
use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use App\Repository\PhotoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        return $this->render('galerie/evenement.html.twig', [
            'evenement' => $evenement,
            'photos' => $photoRepository->findBy(['evenement' => $evenement], ['ordre' => 'ASC']),
        ]);
    }
}
