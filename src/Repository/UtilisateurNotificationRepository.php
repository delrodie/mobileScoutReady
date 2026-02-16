<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UtilisateurNotification>
 */
class UtilisateurNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtilisateurNotification::class);
    }

    /**
     * Récuperation des notifications non lues d'un utilisateur
     */
    public function findNonLuesParUtilisateur(Utilisateur $utilisateur, int $limit = 10): array
    {
        return $this->createQueryBuilder('un')
            ->select('un', 'n')
            ->innerJoin('un.notification', 'n')
            ->where('un.utilisateur = :utilisateur')
            ->andWhere('un.estLue = :estLue')
            ->andWhere('n.estActif = :estActif')
            ->andWhere('(n.expireLe IS NULL OR n.expireLe > :maintenant)')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('estLue', false)
            ->setParameter('estActif', true)
            ->setParameter('maintenant', new \DateTimeImmutable())
            ->orderBy('un.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult()
            ;
    }

    /**
     * Compte des notifications non lue de l'utilisateur
     */
    public function compterNonLuesParUtilisateur(Utilisateur $utilisateur):int
    {
        return $this->createQueryBuilder('un')
            ->select('count(un.id)')
            ->innerJoin('un.notification', 'n')
            ->where('un.utilisateur = :utilisateur')
            ->andWhere('un.estLue = :estLue')
            ->andWhere('n.estActif = :estActif')
            ->andWhere('(n.expireLe IS NULL OR n.expireLe > :maintenant)')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('estLue', false)
            ->setParameter('estActif', true)
            ->setParameter('maintenant', new \DateTimeImmutable())
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * Recuperation de toutes les notifications (lues et non lues ) de l'utilisateur
     */
    public function findToutesParUtilisateur(Utilisateur $utilisateur, $limit = 50)
    {
        return $this->createQueryBuilder('un')
            ->select('un', 'n')
            ->innerJoin('un.notification', 'n')
            ->where('un.utilisateur = :utilisateur')
            ->andWhere('n.estActif = :estActif')
            ->andWhere('(n.expireLe IS NULL OR n.expireLe > :maintenant)')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('estActif', true)
            ->setParameter('maintenant', new \DateTimeImmutable())
            ->orderBy('un.estLue', 'ASC')
            ->addOrderBy('un.creeLe', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Marque toutes les notifications comme lues
     */
    public function marquerToutesCommeLuesPourUtilisateur(Utilisateur $utilisateur): int
    {
        return $this->createQueryBuilder('un')
            ->update()
            ->set('un.estLue', ':vrai')
            ->set('un.lueLe', ':maintenant')
            ->where('un.utilisateur = :utilisateur')
            ->andWhere('un.estLue = :faux')
            ->setParameter('vrai', true)
            ->setParameter('faux', false)
            ->setParameter('maintenant', new \DateTimeImmutable())
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime les anciennes notifications lues (nettoyage)
     */
    public function supprimerAnciennesNotificationsLues(int $joursConservation = 30): int
    {
        $dateLimit = new \DateTimeImmutable("-{$joursConservation} days");

        return $this->createQueryBuilder('un')
            ->delete()
            ->where('un.estLue = :vrai')
            ->andWhere('un.lueLe < :dateLimit')
            ->setParameter('vrai', true)
            ->setParameter('dateLimit', $dateLimit)
            ->getQuery()
            ->execute();
    }

    //    /**
    //     * @return UtilisateurNotification[] Returns an array of UtilisateurNotification objects
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

    //    public function findOneBySomeField($value): ?UtilisateurNotification
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
