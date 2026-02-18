<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Notificationlog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notificationlog>
 */
class NotificationlogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notificationlog::class);
    }

    /**
     * Trouve les logs d'une notification spécifique
     */
    public function findByNotification(Notification $notification): array
    {
        return $this->createQueryBuilder('log')
            ->where('log.notification = :notification')
            ->setParameter('notification', $notification)
            ->orderBy('log.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs d'un utilisateur spécifique
     */
    public function findByUtilisateur(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('log')
            ->where('log.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('log.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'actions par type
     */
    public function countByAction(Notification $notification): array
    {
        $results = $this->createQueryBuilder('log')
            ->select('log.action', 'COUNT(log.id) as total')
            ->where('log.notification = :notification')
            ->setParameter('notification', $notification)
            ->groupBy('log.action')
            ->getQuery()
            ->getResult();

        // Formater le résultat en tableau associatif
        $formatted = [];
        foreach ($results as $result) {
            $formatted[$result['action']] = (int) $result['total'];
        }

        return $formatted;
    }

    /**
     * Trouve les logs récents (X derniers jours)
     */
    public function findRecents(int $jours = 7): array
    {
        $dateDebut = new \DateTimeImmutable("-{$jours} days");

        return $this->createQueryBuilder('log')
            ->where('log.createdAt >= :dateDebut')
            ->setParameter('dateDebut', $dateDebut)
            ->orderBy('log.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime les anciens logs (nettoyage)
     */
    public function supprimerAnciens(int $joursConservation = 90): int
    {
        $dateLimit = new \DateTimeImmutable("-{$joursConservation} days");

        return $this->createQueryBuilder('log')
            ->delete()
            ->where('log.createdAt < :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->getQuery()
            ->execute();
    }

    /**
     * Statistiques globales des notifications
     */
    public function getStatistiquesGlobales(): array
    {
        $qb = $this->createQueryBuilder('log');

        return [
            'total_vues' => $this->countActionTotal(NotificationLog::ACTION_VUE),
            'total_clics' => $this->countActionTotal(NotificationLog::ACTION_CLIQUEE),
            'total_rejets' => $this->countActionTotal(NotificationLog::ACTION_REJETEE),
        ];
    }

    /**
     * Compte le total d'une action spécifique
     */
    private function countActionTotal(string $action): int
    {
        return (int) $this->createQueryBuilder('log')
            ->select('COUNT(log.id)')
            ->where('log.action = :action')
            ->setParameter('action', $action)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Taux de lecture d'une notification (pourcentage)
     */
    public function getTauxLecture(Notification $notification): float
    {
        // Nombre total d'envois
        $totalEnvois = $notification->getUtilisateurNotifications()->count();

        if ($totalEnvois === 0) {
            return 0.0;
        }

        // Nombre de lectures
        $totalLectures = (int) $this->createQueryBuilder('log')
            ->select('COUNT(DISTINCT log.utilisateur)')
            ->where('log.notification = :notification')
            ->andWhere('log.action = :action')
            ->setParameter('notification', $notification)
            ->setParameter('action', NotificationLog::ACTION_VUE)
            ->getQuery()
            ->getSingleScalarResult();

        return round(($totalLectures / $totalEnvois) * 100, 2);
    }

    /**
     * Taux de clic d'une notification (pourcentage)
     */
    public function getTauxClic(Notification $notification): float
    {
        // Nombre total d'envois
        $totalEnvois = $notification->getUtilisateurNotifications()->count();

        if ($totalEnvois === 0) {
            return 0.0;
        }

        // Nombre de clics
        $totalClics = (int) $this->createQueryBuilder('log')
            ->select('COUNT(DISTINCT log.utilisateur)')
            ->where('log.notification = :notification')
            ->andWhere('log.action = :action')
            ->setParameter('notification', $notification)
            ->setParameter('action', NotificationLog::ACTION_CLIQUEE)
            ->getQuery()
            ->getSingleScalarResult();

        return round(($totalClics / $totalEnvois) * 100, 2);
    }


    //    /**
    //     * @return Notificationlog[] Returns an array of Notificationlog objects
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

    //    public function findOneBySomeField($value): ?Notificationlog
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
