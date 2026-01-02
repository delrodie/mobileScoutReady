<?php

namespace App\Repository;

use App\Entity\Reunion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reunion>
 */
class ReunionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reunion::class);
    }

    public function findLast($accademique)
    {
        return $this->createQueryBuilder('r')
            ->where('r.code LIKE :prefix')
            ->setParameter('prefix', 'R-' . $accademique . '-%' )
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult()
            ;
    }

    public function findReunionAvenirForInstance(array $instanceIds)
    {
        return $this->createQueryBuilder('r')
            ->addSelect('i')
            ->leftJoin('r.instance', 'i')
            ->where('i.id IN (:ids)')
            ->andWhere('r.dateAt >= :today')
            ->orderBy('r.dateAt', 'ASC')
            ->setParameter('ids', $instanceIds)
            ->setParameter('today', date('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function findReunionByInstanceAndChamp(?int $champ, array $instanceIds, ?string $branche = null)
    {
        $qb = $this->createQueryBuilder('r')
            ->addSelect('c','i')
            ->join('r.champs', 'c')
            ->join('r.instance', 'i')
            ->where('i.id IN (:ids)')
            ->andWhere('c.id = :champ')
            ->orderBy('r.dateAt', 'DESC')
            ->addOrderBy('r.heureDebut', 'DESC')
            ->setParameter('ids', $instanceIds)
            ->setParameter('champ', $champ)
            ;
        if ($branche){
            $qb->andWhere('r.branche = :branche')
                ->setParameter('branche', $branche);
        }

        return $qb->getQuery()->getResult();
    }

    public function findReunionByChamps(?int $champ)
    {
        return $this->createQueryBuilder('r')
            ->addSelect('c', 'i')
            ->join('r.instance', 'i')
            ->join('r.champs', 'c')
            ->where('c.id = :champ')
            ->orderBy('r.dateAt', 'DESC')
            ->addOrderBy('r.heureDebut', 'DESC')
            ->setParameter('champ', $champ)
            ->getQuery()->getResult()
            ;
    }

    //    /**
    //     * @return Reunion[] Returns an array of Reunion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reunion
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
