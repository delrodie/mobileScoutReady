<?php

namespace App\Repository;

use App\Entity\Fonction;
use App\Services\UtilityService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Fonction>
 */
class FonctionRepository extends ServiceEntityRepository
{
    private UtilityService $utilityService;

    public function __construct(ManagerRegistry $registry, UtilityService $utilityService)
    {
        parent::__construct($registry, Fonction::class);
        $this->utilityService = $utilityService;
    }

    /**
     * @param int $scoutId
     * @return mixed
     */
    public function findAllByScout(int $scoutId): mixed
    {
        return $this->createQueryBuilder('f')
            ->addSelect('s', 'i')
            ->leftJoin('f.scout', 's')
            ->leftJoin('f.instance', 'i')
            ->andWhere('s.id = :scout')
            ->orderBy('f.id', 'DESC')
            ->setParameter('scout', $scoutId)
            ->getQuery()->getResult()
            ;
    }

    public function findOneByScout(int $scoutId)
    {
        return $this->createQueryBuilder('f')
            ->addSelect('s', 'i')
            ->leftJoin('f.scout', 's')
            ->leftJoin('f.instance', 'i')
            ->andWhere('s.id = :scout')
            ->orderBy('f.id', 'DESC')
            ->setParameter('scout', $scoutId)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult()
            ;
    }

    public function findOneByScoutSlug($slug)
    {
        $uuid = $this->utilityService->convertSlugToUuid($slug);
        if (!$uuid) {
            return null;
        }

        return $this->query()
            ->where('s.slug = :slug')
            ->orderBy('f.id', 'DESC')
            ->setParameter('slug', $uuid, 'uuid')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }



    public function findCommunauteByBranche(int $profil, int $instance, string $branche, string $annee, string $statut = 'JEUNE')
    {
        return $this->query()
            ->where('i.instanceParent = :instance')
            ->andWhere('f.branche = :branche')
            ->andWhere('f.annee = :annee')
            ->andWhere('s.id <> :profil')
            ->andWhere('s.statut = :statut')
            ->setParameter('profil', $profil)
            ->setParameter('annee', $annee)
            ->setParameter('branche', $branche)
            ->setParameter('instance', $instance)
            ->setParameter('statut', $statut)
            ->getQuery()->getResult()
            ;
    }

    public function findCommunauteByDistrict(int $profil, int $instance, string $annee)
    {
        return $this->query()
            ->where('i.id = :instance')
            ->orWhere('i.instanceParent = :instance')
            ->andWhere('s.id <> :profil')
            ->andWhere('f.annee = :annee')
            ->setParameter('instance', $instance)
            ->setParameter('profil', $profil)
            ->setParameter('annee', $annee)
            ->getQuery()->getResult()
            ;
    }

    public function findCommunauteByGroupe(int $profil, int $instance, string $annee)
    {
        return $this->query()
            ->where('i.id = :instance')
            ->andWhere('s.id <> :profil')
            ->andWhere('f.annee = :annee')
            ->setParameter('instance', $instance)
            ->setParameter('profil', $profil)
            ->setParameter('annee', $annee)
            ->getQuery()->getResult()
            ;
    }

    public function query()
    {
        return $this->createQueryBuilder('f')
            ->addSelect('s', 'i')
            ->innerJoin('f.scout', 's')
            ->leftJoin('f.instance', 'i');
    }


    //    /**
    //     * @return Fonction[] Returns an array of Fonction objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Fonction
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
