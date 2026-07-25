<?php

namespace App\Repository;

use App\Entity\AccesClient;
use App\Entity\Client;
use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccesClient>
 */
class AccesClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccesClient::class);
    }

    public function existeAcces(Client $client, Evenement $evenement): bool
    {
        return null !== $this->findOneBy(['client' => $client, 'evenement' => $evenement]);
    }
}
