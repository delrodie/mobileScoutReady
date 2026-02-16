<?php

namespace App\Services;

use App\Entity\Notification;
use App\Entity\Notificationlog;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurNotification;
use App\Repository\NotificationRepository;
use App\Repository\UtilisateurNotificationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;

class NotificationService
{
    public function __construct(
        private EntityManager          $entityManager,
        private NotificationRepository $notificationRepository,
        private RequestStack           $requestStack,
        private UtilisateurRepository  $utilisateurRepository,
        private readonly UtilisateurNotificationRepository $utilisateurNotificationRepository
    )
    {
    }

    /**
     * Envoie de notification à tous les utilisateurs
     */
    public function envoyerATous(Notification $notification)
    {
        $utilisateurs = $this->utilisateurRepository->findAll();
        foreach ($utilisateurs as $utilisateur) {
            $this->creerUtilisateurNotification($utilisateur, $notification);
        }

        $notification->setTypeCible(Notification::TARGET_ALL);
        $this->entityManager->flush();
    }

    /**
     * Envoyer a un utilisateur specifique
     */
    public function envoyerAUtilisateur(Utilisateur $utilisateur, Notification $notification): void
    {
        $this->creerUtilisateurNotification($utilisateur, $notification);
        $notification->setTypeCible(Notification::TARGET_SPECIFIC);
        $this->entityManager->flush();
    }

    /**
     * Création d'une UtilisateurNotification
     */
    private function creerUtilisateurNotification(Utilisateur $utilisateur, Notification $notification): UtilisateurNotification
    {
        $utilisateurNotification = new UtilisateurNotification();
        $utilisateurNotification->setUtilisateur($utilisateur);
        $utilisateurNotification->setNotification($notification);

        $this->entityManager->persist($utilisateurNotification);

        return $utilisateurNotification;
    }

    /**
     * Marquer une notification comme lue
     */
    public function marqueeCommeLue(UtilisateurNotification $utilisateurNotification, Utilisateur $utilisateur): void
    {
        if ($utilisateurNotification->getUtilisateur() !== $utilisateur){
            throw new \LogicException('Non autorisé');
        }

        if (!$utilisateurNotification->isEstLue()){
            $utilisateurNotification->marquerCommeLue();
            $this->enregistrerAction($utilisateur, $utilisateurNotification->getNotification(), Notificationlog::ACTION_VUE);
            $this->entityManager->flush();
        }
    }

    private function enregistrerAction(?Utilisateur $utilisateur, ?Notification $notification, string $action): void
    {
        $log = new Notificationlog();
        $log->setUtilisateur($utilisateur);
        $log->setNotification($notification);
        $log->setAction($action);

        $request = $this->requestStack->getCurrentRequest();
        if ($request){
            $log->setUserAgent($request->headers->get('User-Agent'));
            $log->setAdresseIp($request->getClientIp());
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * Marquer toutes les notifications comme lues pour un utilisateur
     */
    public function marquerToutesCommeLues(Utilisateur $utilisateur): int
    {
        return $this->utilisateurNotificationRepository->marquerToutesCommeLuesPourUtilisateur($utilisateur);
    }

    public function creerNotificationRapide(string $titre, string $message, string $type = Notification::TYPE_INFO, ?string $urlAction = null, ?string $libelleAction = null)
    {
        $notification = new Notification();
        $notification->setTitre($titre);
        $notification->setMessage($message);
        $notification->setType($type);
        $notification->setUrlAction($urlAction);
        $notification->setLibelleAction($libelleAction);

        $this->entityManager->persist($notification);

        return $notification;
    }

    /**
     * Nettoyer les anciennes notifications
     */
    public function nettoyerAnciennesNotifications(int $jourConservation = 30): int
    {
        return $this->utilisateurNotificationRepository->supprimerAnciennesNotificationsLues($jourConservation);
    }


}
