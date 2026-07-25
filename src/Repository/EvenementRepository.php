<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * @return Evenement[]
     */
    public function findAccessiblesPour(Client $client): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.accesClients', 'a')
            ->andWhere('a.client = :client')
            ->setParameter('client', $client)
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
