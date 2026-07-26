<?php

namespace App\Controller\Galerie;

use App\Security\PrenomSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PrenomController extends AbstractController
{
    #[Route('/prenom', name: 'client_prenom', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function prenom(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('client_prenom', $request->request->get('_csrf_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $prenom = trim((string) $request->request->get('prenom', ''));

            if ('' === $prenom) {
                $this->addFlash('erreur', 'Merci d\'indiquer votre prénom.');
            } else {
                PrenomSession::set($request, $prenom);

                return $this->redirectToRoute('client_mes_evenements');
            }
        }

        return $this->render('galerie/prenom.html.twig');
    }
}
