<?php

namespace App\Repository;

use App\Entity\Assister;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assister>
 */
class AssisterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assister::class);
    }

    public function findPresenceByReunion(?int $reunion, ?string $search, ?int $limit, ?int $offset = 0)
    {
        $qb = $this->query()
            ->where('r.id = :reunion')
            ->orderBy('s.nom', 'ASC')
            ->addOrderBy('s.prenom', 'ASC')
            ->setParameter('reunion', $reunion)
            ;

        if ($search){
            $qb->andWhere('s.nom LIKE :search OR s.prenom LIKE :search OR a.pointageAt LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()->getResult()
            ;
    }

    public function countPresenceByReunion(?int $reunion, ?string $search)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->leftJoin('a.reunion', 'r')
            ->leftJoin('a.scout', 's')
            ->where('r.id = :reunion')
            ->setParameter('reunion', $reunion)
            ;
        if ($search){
            $qb->andWhere('s.nom LIKE :search OR s.prenom LIKE :search OR a.pointageAt LIKE :search')
                ->setParameter('search', '%'.$search.'%')
                ;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function query()
    {
        return $this->createQueryBuilder('a')
            ->addSelect('r', 's')
            ->leftJoin('a.reunion', 'r')
            ->leftJoin('a.scout', 's')
            ;
    }

    //    /**
    //     * @return Assister[] Returns an array of Assister objects
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

    //    public function findOneBySomeField($value): ?Assister
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
