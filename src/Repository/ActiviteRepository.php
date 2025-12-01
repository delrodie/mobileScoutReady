<?php

namespace App\Repository;

use App\Entity\Activite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activite>
 */
class ActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    public function findActivitesAvenirForInstances(array $instanceIds)
    {
        return $this->createQueryBuilder('a')
            ->addSelect('i')
            ->leftJoin('a.instance', 'i')
            ->where('i.id IN (:ids)')
            ->andWhere('(a.dateDebutAt <= :today AND a.dateFinAt >= :today) OR (a.dateDebutAt >= :today)')
            ->orderBy('a.dateDebutAt', 'ASC')
            ->setParameter('ids', $instanceIds)
            ->setParameter('today', date('Y-m-d'))
            ->getQuery()
            ->getResult();
    }


//    /**
//     * @return Activite[] Returns an array of Activite objects
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

//    public function findOneBySomeField($value): ?Activite
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
