<?php

namespace App\Services;

use App\Entity\Activite;
use App\Entity\Notification;
use App\Entity\Utilisateur;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\MessagingException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FcmNotificationService
{
    public function __construct(
        private readonly Messaging       $messaging,
        private readonly LoggerInterface $logger, private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * Envoyer une push à un utilisateur
     */
    public function envoyerAUtilisateur(Utilisateur $utilisateur, Notification $notification): bool
    {
        $token = $utilisateur->getFcmToken();
        if (!$token) return false;

        return $this->envoyer([$token], $notification) > 0;
    }

    /**
     * Envoyer à plusieurs utilisateurs (multicast)
     */
    public function envoyerAUtilisateurs(array $utilisateurs, Notification $notification): int
    {
        $tokens = array_filter(
            array_map(fn(Utilisateur $u) => $u->getFcmToken(), $utilisateurs)
        );

        if (empty($tokens)) return 0;

        return $this->envoyer(array_values($tokens), $notification);
    }

    public function notifierActiviteAuxUtilisateurs(array $utilisateurs, Notification $notification, Activite $activite): int
    {
        $tokens = array_filter(
            array_map(fn(Utilisateur $u) => $u->getFcmToken(), $utilisateurs)
        );

        if (empty($tokens)) return 0;

        return $this->envoyerActivite(array_values($tokens), $notification, $activite);
    }

    /**
     * Envoie réel via kreait (multicast)
     */
    private function envoyer(array $tokens, Notification $notification): int
    {
        try {
            $message = CloudMessage::new()
                ->withNotification(
                    FcmNotification::create(
                        $notification->getTitre(),
                        $notification->getMessage()
                    )
                )
                ->withData([
                    'notificationId' => (string) ($notification->getId() ?? ''),
                    'type'           => $notification->getType() ?? 'info',
                    'url'            => $notification->getUrlAction() ?? '',
                    'icone'          => $notification->getIcone() ?? 'ic_launcher',
                ])
                ->withAndroidConfig([
                    'notification' => [
                        'sound'       => 'default',
                        'channel_id'  => 'notifications',
                        'icon' => 'ic_launcher'
                    ],
                    'priority' => 'high',
                ])
                ->withApnsConfig([
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ]);

            // Envoi multicast (gère 1 ou plusieurs tokens)
            $report = $this->messaging->sendMulticast($message, $tokens);

            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    $this->logger->warning('FCM échec token: ' . $failure->error()?->getMessage());
                }
            }

            return $report->successes()->count();

        } catch (MessagingException | InvalidMessage $e) {
            $this->logger->error('FCM erreur envoi: ' . $e->getMessage());
            return 0;
        }
    }

    private function envoyerActivite(array $tokens, Notification $notification, Activite $activite): int
    {
        try {
            $imageUrl = $this->urlGenerator->generate('app_home').'uploads/activites/affiche/'. $activite->getAffiche();

            $message = CloudMessage::new()
                ->withNotification(
                    FcmNotification::create(
                        $notification->getTitre(),
                        $notification->getMessage()
                    )->withImageUrl($imageUrl)
                )
                ->withData([
                    'notificationId' => (string) ($notification->getId() ?? ''),
                    'type'           => $notification->getType() ?? 'info',
                    'url'            => $notification->getUrlAction() ?? $this->urlGenerator->generate('app_activite_index'),
                    'icon'          => $notification->getIcone() ?? 'ic_launcher',
                ])
                ->withAndroidConfig([
                    'notification' => [
                        'sound'       => 'default',
                        'channel_id'  => 'notifications',
                        'icon' => 'ic_launcher',
                        'image'       => $imageUrl,
                    ],
                    'priority' => 'high',
                ])
                ->withApnsConfig([
                    'payload' => [
                        'aps' => [
                            'mutable-content' => 1,
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                    'fcm_options' => [
                        'image' => $imageUrl
                    ]
                ]);

            // Envoi multicast (gère 1 ou plusieurs tokens)
            $report = $this->messaging->sendMulticast($message, $tokens);

            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    $this->logger->warning('FCM échec token: ' . $failure->error()?->getMessage());
                }
            }

            return $report->successes()->count();

        } catch (MessagingException | InvalidMessage $e) {
            $this->logger->error('FCM erreur envoi: ' . $e->getMessage());
            return 0;
        }
    }
}
