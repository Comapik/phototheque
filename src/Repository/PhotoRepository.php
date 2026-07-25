<?php

namespace App\Repository;

use App\Entity\Evenement;
use App\Entity\Photo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Photo>
 */
class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    public function prochainOrdre(Evenement $evenement): int
    {
        $max = $this->createQueryBuilder('p')
            ->select('MAX(p.ordre)')
            ->andWhere('p.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return null === $max ? 0 : $max + 1;
    }
}
