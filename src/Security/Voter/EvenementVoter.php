<?php

namespace App\Security\Voter;

use App\Entity\Client;
use App\Entity\Evenement;
use App\Repository\AccesClientRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EvenementVoter extends Voter
{
    public const VOIR = 'EVENEMENT_VOIR';

    public function __construct(
        private readonly AccesClientRepository $accesClientRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VOIR === $attribute && $subject instanceof Evenement;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (in_array('ROLE_ADMIN', $token->getRoleNames(), true)) {
            return true;
        }

        if (!$user instanceof Client) {
            return false;
        }

        /** @var Evenement $evenement */
        $evenement = $subject;

        return $this->accesClientRepository->existeAcces($user, $evenement);
    }
}
