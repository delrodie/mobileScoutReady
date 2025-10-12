<?php

namespace App\Repository;

use App\Entity\Instance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Instance>
 */
class InstanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Instance::class);
    }

    public function createOrderedQueryBuilder()
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.nom', 'ASC');
    }

    public function findByQuery(string $parent, string $type, string $nom)
    {
        $query = $this->createQueryBuilder('i')
            ->addSelect('p')
            ->leftJoin('i.instanceParent', 'p')
            ->where('i.type = :type AND i.nom = :nom');

        $type === 'REGION'
            ? $query->andWhere('p.sigle = :parent')
            : $query->andWhere('p.nom = :parent');

            $query->setParameter('parent', $parent)
            ->setParameter('type', $type)
            ->setParameter('nom', $nom);

            return $query->getQuery()->getResult();
    }


    //    /**
    //     * @return Instance[] Returns an array of Instance objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Instance
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
