<?php

namespace App\Repository;

use App\Entity\InfosComplementaire;
use App\Services\UtilityService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InfosComplementaire>
 */
class InfosComplementaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry,
                                private readonly UtilityService $utilityService
    )
    {
        parent::__construct($registry, InfosComplementaire::class);
    }

    public function findByScoutSlug(string $slug)
    {
        $uuid = $this->utilityService->convertSlugToUuid($slug);
        if (!$uuid) {
            return null;
        }

        return $this->createQueryBuilder('i')
            ->addSelect('s')
            ->leftJoin('i.scout', 's')
            ->where('s.slug = :slug')
            ->setParameter('slug', $uuid, 'uuid')
            ->getQuery()->getOneOrNullResult()
            ;
    }

    //    /**
    //     * @return InfosComplementaire[] Returns an array of InfosComplementaire objects
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

    //    public function findOneBySomeField($value): ?InfosComplementaire
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
