<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Evenement;
use App\Form\ClientType;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AccueilController extends AbstractController
{
    #[Route('/admin', name: 'admin_accueil', methods: ['GET'])]
    public function accueil(EvenementRepository $evenementRepository): Response
    {
        return $this->render('admin/accueil.html.twig', [
            'evenements' => $evenementRepository->findBy([], ['date' => 'DESC']),
            'formEvenement' => $this->createForm(EvenementType::class, new Evenement()),
            'formClient' => $this->createForm(ClientType::class, new Client()),
        ]);
    }
}
