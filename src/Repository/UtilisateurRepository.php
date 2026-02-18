<?php

namespace App\Repository;

use App\Entity\Fonction;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function findByScoutStatut(string $scoutStatut)
    {
        return  $this->createQueryBuilder('u')
            ->addSelect('s')
            ->innerJoin('u.scout', 's')
            ->where('s.statut = :statut')
            ->setParameter('statut', $scoutStatut)
            ->getQuery()->getResult()
            ;
    }

    public function findByPoste(string $poste)
    {
        return $this->createQueryBuilder('u')
            ->addSelect('s', 'f')
            ->innerJoin('u.scout', 's')
            ->innerJoin(Fonction::class, 'f', 'WITH', 'f.scout = s')
            ->where('f.poste = :poste')
            ->setParameter('poste', $poste)
            ->getQuery()->getResult()
            ;
    }

    public function findByDetailPoste(array $detailPoste)
    {
        return $this->createQueryBuilder('u')
            ->addSelect('s', 'f')
            ->innerJoin('u.scout', 's')
            ->innerJoin(Fonction::class, 'f', 'WITH', 'f.scout = s')
            ->where('f.detailPoste IN (:details)')
            ->setParameter('details', $detailPoste)
            ->getQuery()->getResult()
            ;
    }

    public function findByBranche(string $branche)
    {
        return $this->createQueryBuilder('u')
            ->addSelect('s', 'f')
            ->innerJoin('u.scout', 's')
            ->innerJoin(Fonction::class, 'f', 'WITH', 'f.scout = s')
            ->where('f.branche = :branche')
            ->setParameter('branche', $branche)
            ->getQuery()->getResult()
            ;
    }

    //    /**
    //     * @return Utilisateur[] Returns an array of Utilisateur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Utilisateur
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
