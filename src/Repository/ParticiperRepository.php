<?php

namespace App\Repository;

use App\Entity\Participer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participer>
 */
class ParticiperRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participer::class);
    }

    public function findPresenceByActivite(?int $activite)
    {
        return $this->query()
            ->where('a.id = :activite')
            ->orderBy('s.nom', 'ASC')
            ->addOrderBy('s.prenom', 'ASC')
            ->setParameter('activite', $activite)
            ->getQuery()->getResult()
            ;
    }

    private function query(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->addSelect('a', 's')
            ->leftJoin('p.activite', 'a')
            ->leftJoin('p.scout', 's')
            ;
    }

    //    /**
    //     * @return Participer[] Returns an array of Participer objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Participer
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

}
