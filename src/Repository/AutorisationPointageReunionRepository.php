<?php

namespace App\Repository;

use App\Entity\AutorisationPointageReunion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AutorisationPointageReunion>
 */
class AutorisationPointageReunionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutorisationPointageReunion::class);
    }

    public function findPointeurs($reunion)
    {
        return $this->query()
            ->where('r.id = :id')
            ->orderBy('u.role', 'ASC')
            ->addOrderBy('s.nom', 'ASC')
            ->addOrderBy('s.prenom', 'ASC')
            ->setParameter('id', $reunion)
            ->getQuery()->getResult()
            ;
    }

    public function findAutorisation($scout, $reunion)
    {
        return $this->query()
            ->where('s.id = :scout')
            ->andWhere('r.id = :reunion')
            ->setParameter('scout', $scout)
            ->setParameter('reunion', $reunion)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult()
            ;
    }

    public function query()
    {
        return $this->createQueryBuilder('u')
            ->addSelect('r', 's')
            ->leftJoin('u.reunion', 'r')
            ->leftJoin('u.scout', 's')
            ;
    }

    //    /**
    //     * @return AutorisationPointageReunion[] Returns an array of AutorisationPointageReunion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AutorisationPointageReunion
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
