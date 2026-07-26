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
    public const UPLOAD = 'EVENEMENT_UPLOAD';

    public function __construct(
        private readonly AccesClientRepository $accesClientRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VOIR, self::UPLOAD], true) && $subject instanceof Evenement;
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

        if (!$this->accesClientRepository->existeAcces($user, $evenement)) {
            return false;
        }

        return self::VOIR === $attribute || $evenement->isUploadClientAutorise();
    }
}
