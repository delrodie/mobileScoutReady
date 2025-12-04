<?php

namespace App\Repository;

use App\Entity\AutorisationPointageActivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AutorisationPointageActivite>
 */
class AutorisationPointageActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutorisationPointageActivite::class);
    }

    public function findPointeurs($activite)
    {
        return $this->query()
            ->where('a.id = :id')
            ->orderBy('u.role', 'ASC')
            ->addOrderBy('s.nom', 'ASC')
            ->addOrderBy('s.prenom', 'ASC')
            ->setParameter('id', $activite)
            ->getQuery()->getResult();
    }

    public function findAutorisation($scout, $activite)
    {
        return $this->query()
            ->where('s.id = :scout')
            ->andWhere('a.id = :activite')
            ->setParameter('scout', $scout)
            ->setParameter('activite', $activite)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    public function query()
    {
        return $this->createQueryBuilder('u')
            ->addSelect('a', 's')
            ->leftJoin('u.activite', 'a')
            ->leftJoin('u.scout', 's');
    }



    //    /**
    //     * @return AutorisationPointageActivite[] Returns an array of AutorisationPointageActivite objects
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

    //    public function findOneBySomeField($value): ?AutorisationPointageActivite
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
