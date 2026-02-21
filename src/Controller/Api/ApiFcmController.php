<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fcm')]
class ApiFcmController extends AbstractController
{
    public function __construct(
        private readonly ApiNotificationController $apiNotificationController,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    #[Route('/enregistrement', name: 'api_notification_fcm_enregistrement', methods: ['POST'])]
    public function enregistrementFcmToken(Request $request): JsonResponse
    {
        $utilisateur = $this->apiNotificationController->getUtilisateurByRequest($request);
        if ($utilisateur instanceof JsonResponse) return $utilisateur;

        $donnees = json_decode($request->getContent(), true);
        $fcmToken = $donnees['fcmToken'] ?? null;
        $platform = $donnees['platform'] ?? null;

        if (!$fcmToken) {
            return $this->json(['error' => 'Token FCM manquant'], 400);
        }

        $utilisateur->setFcmToken($fcmToken);
        $utilisateur->setFcmPlatform($platform);
        $utilisateur->setFcmTokenUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }
}
