<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Stocke en session le prénom saisi par le client après connexion, afin de
 * l'associer aux photos qu'il ajoute (cf. Photo::$prenomUploadeur).
 * Un code d'accès étant partagé par plusieurs personnes, ce prénom identifie
 * la personne derrière la session en cours, pas le Client lui-même.
 */
final class PrenomSession
{
    private const CLE = 'client_prenom';

    private function __construct()
    {
    }

    public static function get(Request $request): ?string
    {
        return $request->getSession()->get(self::CLE);
    }

    public static function set(Request $request, string $prenom): void
    {
        $request->getSession()->set(self::CLE, $prenom);
    }
}
