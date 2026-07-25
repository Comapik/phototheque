<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/admin/connexion', name: 'admin_connexion', methods: ['GET', 'POST'])]
    public function connexion(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_clients');
        }

        return $this->render('admin/connexion.html.twig', [
            'dernier_identifiant' => $authenticationUtils->getLastUsername(),
            'erreur' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/admin/deconnexion', name: 'admin_deconnexion', methods: ['GET'])]
    public function deconnexion(): never
    {
        throw new \LogicException('Interceptée par le firewall avant d\'atteindre ce contrôleur.');
    }
}
