<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Trouve toutes les notifications actives et non expirées
     */
    public function findActives(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.estActif = :actif')
            ->andWhere('(n.expireLe IS NULL OR n.expireLe > :maintenant)')
            ->setParameter('actif', true)
            ->setParameter('maintenant', new \DateTimeImmutable())
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les notifications créées dans les X derniers jours
     */
    public function findRecentes(int $jours = 7): array
    {
        $dateDebut = new \DateTimeImmutable("-{$jours} days");

        return $this->createQueryBuilder('n')
            ->where('n.createdAt >= :dateDebut')
            ->setParameter('dateDebut', $dateDebut)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de notifications envoyées
     */
    public function countEnvoyees(): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->innerJoin('n.utilisateurNotifications', 'un')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les notifications expirées à supprimer
     */
    public function findExpirees(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.expireLe IS NOT NULL')
            ->andWhere('n.expireLe < :maintenant')
            ->setParameter('maintenant', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime les notifications expirées
     */
    public function supprimerExpirees(): int
    {
        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.expireLe IS NOT NULL')
            ->andWhere('n.expireLe < :maintenant')
            ->setParameter('maintenant', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    //    /**
    //     * @return Notification[] Returns an array of Notification objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Notification
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
