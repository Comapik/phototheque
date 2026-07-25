<?php

namespace App\Controller\Galerie;

use App\Entity\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'accueil', methods: ['GET'])]
    public function accueil(Request $request): Response
    {
        if ($this->getUser() instanceof Client) {
            return $this->redirectToRoute('client_mes_evenements');
        }

        // Le pare-feu admin est distinct (pattern ^/admin) : on ne peut pas
        // lire son utilisateur via getUser() ici. On vérifie simplement la
        // présence du token en session, stockée par Symfony sous cette clé.
        if ($request->hasSession() && $request->getSession()->has('_security_admin')) {
            return $this->redirectToRoute('admin_accueil');
        }

        return $this->render('galerie/accueil.html.twig');
    }
}
