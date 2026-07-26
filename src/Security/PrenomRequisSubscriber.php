<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Empêche un client d'atteindre une page de la galerie sans être passé par
 * l'étape de saisie du prénom (accessible directement via /mes-evenements
 * sinon, en contournant la redirection de CodeAccesAuthenticator).
 */
class PrenomRequisSubscriber implements EventSubscriberInterface
{
    private const ROUTES_EXEMPTEES = ['client_connexion', 'client_deconnexion', 'client_prenom'];

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');

        if (!is_string($route) || !str_starts_with($route, 'client_') || in_array($route, self::ROUTES_EXEMPTEES, true)) {
            return;
        }

        if (!$this->security->isGranted('ROLE_CLIENT')) {
            return;
        }

        if (null !== PrenomSession::get($event->getRequest())) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('client_prenom')));
    }
}
