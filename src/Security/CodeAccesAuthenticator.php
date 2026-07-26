<?php

namespace App\Security;

use App\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Authentifie un Client à partir d'un simple code d'accès (pas d'identifiant),
 * en le comparant à tous les codes existants. Le nombre de clients reste
 * faible (deux photographes, quelques dizaines d'événements), donc la
 * recherche linéaire est largement suffisante.
 */
class CodeAccesAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public const LOGIN_ROUTE = 'client_connexion';

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $code = (string) $request->request->get('code', '');

        if ('' === $code) {
            throw new CustomUserMessageAuthenticationException('Merci de saisir un code d\'accès.');
        }

        $client = null;
        foreach ($this->clientRepository->findAll() as $candidat) {
            if ($this->passwordHasher->isPasswordValid($candidat, $code)) {
                $client = $candidat;
                break;
            }
        }

        if (null === $client) {
            throw new CustomUserMessageAuthenticationException('Code d\'accès invalide.');
        }

        $clientId = (string) $client->getId();

        return new SelfValidatingPassport(
            new UserBadge($clientId, fn () => $client),
            [new CsrfTokenBadge('client_connexion', $request->request->get('_csrf_token'))]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Le prénom est redemandé à chaque connexion : un code d'accès étant
        // partagé, il permet de savoir qui, parmi les personnes qui le
        // partagent, ajoute quelles photos (cf. PrenomController).
        return new RedirectResponse($this->urlGenerator->generate('client_prenom'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }
}
