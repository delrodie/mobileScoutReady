<?php

namespace App\Services;

use App\Entity\Activite;
use App\Entity\Notification;
use App\Entity\Notificationlog;
use App\Entity\Scout;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurNotification;
use App\Repository\NotificationRepository;
use App\Repository\UtilisateurNotificationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface                     $entityManager,
        private NotificationRepository                     $notificationRepository,
        private RequestStack                               $requestStack,
        private UtilisateurRepository                      $utilisateurRepository,
        private readonly UtilisateurNotificationRepository $utilisateurNotificationRepository,
        private readonly NotificationCibleService          $cibleService,
        private readonly FcmNotificationService            $fcmService, private readonly UrlGeneratorInterface $urlGenerator
    )
    {
    }

    /**
     * Envoie de notification à tous les utilisateurs
     */
    public function envoyerATous(Notification $notification): void
    {
        $utilisateurs = $this->utilisateurRepository->findAll();
        foreach ($utilisateurs as $utilisateur) {
            $this->creerUtilisateurNotification($utilisateur, $notification);
        }

        $notification->setTypeCible(Notification::TARGET_ALL);
        $this->entityManager->flush();

        $this->fcmService->envoyerAUtilisateurs($utilisateurs, $notification);
    }

    /**
     * Envoyer a un utilisateur specifique
     */
    public function envoyerAUtilisateur(Utilisateur $utilisateur, Notification $notification): void
    {
        $this->creerUtilisateurNotification($utilisateur, $notification);
        $notification->setTypeCible(Notification::TARGET_SPECIFIC);
        $this->entityManager->flush();

        $this->fcmService->envoyerAUtilisateur($utilisateur, $notification);
    }

    /**
     * Envoie une notification à plusieurs utilisateurs
     */
    public function envoyerAUtilisateurs(array $utilisateurs, Notification $notification): void
    {
        foreach ($utilisateurs as $utilisateur) {
            $this->creerUtilisateurNotification($utilisateur, $notification);
        }

        $notification->setTypeCible(Notification::TARGET_SPECIFIC);
        $this->entityManager->flush();
    }

    /**
     * Envoie une notification à une cible prédéfinie (groupe)
     */
    public function envoyerACible(string $cible, Notification $notification): void
    {
        $utilisateurs = $this->cibleService->getUtilisateursParCible($cible);

        foreach ($utilisateurs as $utilisateur) {
            $this->creerUtilisateurNotification($utilisateur, $notification);
        }

        $notification->setTypeCible(Notification::TARGET_SPECIFIC);
        $notification->setCible($cible);
        $this->entityManager->flush();

        $this->fcmService->envoyerAUtilisateurs($utilisateurs, $notification);
    }

    /**
     * Envoyer a un utilisateur specifique
     */
    public function envoyerASuperAdmin(Scout $scout): void
    {
//        $notification = $this->notificationRepository->findOneBy(['titre' => 'Nouvel inscrit !']);
        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => "0709321521"]);
        if ($utilisateur){
            $notification = new Notification();
            $notification->setTitre("Nouvel inscrit");
            $notification->setMessage("{$scout->getNom()} {$scout->getPrenom()} vient de s'inscrire");
            $notification->setTypeCible(Notification::TARGET_SPECIFIC);
            $notification->setType("info");
            $notification->setUrlAction($this->urlGenerator->generate('app_communaute_membre',['slug' => $scout->getSlug()]));
            $notification->setLibelleAction("Voir le profil");
            $notification->setIcone("bi-award");
            $notification->setEstActif(true);
            $this->entityManager->persist($notification);

            $this->creerUtilisateurNotification($utilisateur, $notification);
            $this->entityManager->flush();

            $this->fcmService->envoyerAUtilisateur($utilisateur, $notification);
        }
    }

    /**
     * Notifier l'activité dans à la cible
     */
    public function notifierActivite(Activite $activite): void
    {
        // Creation de la notification
        $notification = new Notification();
        $notification->setTitre($activite->getTitre());
        $notification->setTypeCible(Notification::TARGET_ALL);
        $notification->setType(Notification::TYPE_INFO);
        $notification->setEstActif(true);
        $notification->setMessage($activite->getDescription());
        $notification->setUrlAction($this->urlGenerator->generate('app_activite_show',['id' => $activite->getId()]));
        $notification->setLibelleAction("Voir les details");
//        $notification->setExpireLe(new \DateTimeImmutable($activite->getDateFinAt()));
        $notification->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $utilisateurs = $this->utilisateurRepository->findAll();
        foreach ($utilisateurs as $utilisateur) {
            $this->creerUtilisateurNotification($utilisateur, $notification);
        }

        $notification->setTypeCible(Notification::TARGET_ALL);
        $this->entityManager->flush();

        $this->fcmService->notifierActiviteAuxUtilisateurs($utilisateurs, $notification, $activite);

        // Gestion des utilisation
//        foreach ($activite->getCible() as $cible) {
//            $utilisateurs = $this->cibleService->getUtilisateursParCible($cible);
//
//            if ($utilisateurs){
//                foreach ($utilisateurs as $utilisateur) {
//                    $this->creerUtilisateurNotification($utilisateur, $notification);
//                }
//
//                $notification->setTypeCible(Notification::TARGET_SPECIFIC);
//                $notification->setCible($cible);
//                $this->entityManager->flush();
//
//                $this->fcmService->notifierActiviteAuxUtilisateurs($utilisateurs, $notification, $activite);
//            }
//        }

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

    public function enregistrerAction(?Utilisateur $utilisateur, ?Notification $notification, string $action): void
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
