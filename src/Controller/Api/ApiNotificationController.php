<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\NotificationLog;
use App\Entity\UtilisateurNotification;
use App\Repository\ScoutRepository;
use App\Repository\UtilisateurNotificationRepository;
use App\Repository\UtilisateurRepository;
use App\Services\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notification')]
class ApiNotificationController extends AbstractController
{
    public function __construct(
        private readonly ScoutRepository $scoutRepository,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly UtilisateurNotificationRepository $utilisateurNotificationRepository,
        private readonly NotificationService $notificationService
    ) {
    }

    /**
     * Toutes les notifications (lues + non lues)
     */
    #[Route('/', name: 'api_notification_all', methods: ['GET', 'POST'])]
    public function getAll(Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurByRequest($request);
        if ($utilisateur instanceof JsonResponse) return $utilisateur;

        $limit = $request->query->getInt('limit', 50);
        $notifications = $this->utilisateurNotificationRepository
            ->findToutesParUtilisateur($utilisateur, $limit);

        return $this->json([
            'notifications' => array_map(
                fn($un) => $this->serialiserUtilisateurNotification($un),
                $notifications
            ),
        ]);
    }

    /**
     * Notifications non lues uniquement
     */
    #[Route('/non-lues', name: 'api_notification_non_lues', methods: ['GET', 'POST'])]
    public function getNonLues(Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurByRequest($request);
        if ($utilisateur instanceof JsonResponse) return $utilisateur;

        $limit = $request->query->getInt('limit', 10);
        $notifications = $this->utilisateurNotificationRepository
            ->findNonLuesParUtilisateur($utilisateur, $limit);

        return $this->json([
            'notifications' => array_map(
                fn($un) => $this->serialiserUtilisateurNotification($un),
                $notifications
            ),
            'count' => $this->utilisateurNotificationRepository
                ->compterNonLuesParUtilisateur($utilisateur),
        ]);
    }

    /**
     * Compteur seul (pour le badge)
     */
    #[Route('/count/non-lues', name: 'api_notification_count_non_lues', methods: ['GET', 'POST'])]
    public function getCountNonLues(Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurByRequest($request);
        if ($utilisateur instanceof JsonResponse) return $utilisateur;

        $count = $this->utilisateurNotificationRepository
            ->compterNonLuesParUtilisateur($utilisateur);

        return $this->json(['count' => $count], Response::HTTP_OK);
    }

    /**
     * Marquer une notification comme lue
     */
    #[Route('/{id}/marquer/comme-lue', name: 'api_notification_marquer_comme_lue', methods: ['GET', 'POST'])]
    public function marquerCommeLue(
        Request $request,
        UtilisateurNotification $utilisateurNotification
    ): JsonResponse {
        $utilisateur = $this->getUtilisateurByRequest($request);
        if ($utilisateur instanceof JsonResponse) return $utilisateur;

        try {
            $this->notificationService->marqueeCommeLue($utilisateurNotification, $utilisateur);

            return $this->json([
                'success'      => true,
                'countNonLues' => $this->utilisateurNotificationRepository
                    ->compterNonLuesParUtilisateur($utilisateur),
            ]);
        } catch (\LogicException $e) {
            return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }
    }

    /**
     * Logger un clic sur une notification
     */
    #[Route('/log/{id}/clic', name: 'api_notification_log_clic', methods: ['GET', 'POST'])]
    public function logClic(
        Request $request,
        UtilisateurNotification $utilisateurNotification
    ): JsonResponse {
        $utilisateur = $this->getUtilisateurByRequest($request);
        if ($utilisateur instanceof JsonResponse) return $utilisateur;

        $this->notificationService->enregistrerAction(
            $utilisateur,
            $utilisateurNotification->getNotification(),
            NotificationLog::ACTION_CLIQUEE
        );

        return $this->json(['success' => true]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    #[Route('/marquee/toutes/lues/utilisateur', name: 'api_notification_marquee_toutes_lues', methods: ['GET', 'POST'])]
    public function marqueeToutesLues(Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurByRequest($request);
        if ($utilisateur instanceof JsonResponse) return $utilisateur;

        $count = $this->notificationService->marquerToutesCommeLues($utilisateur);

        return $this->json([
            'success'        => true,
            'nombreMarquees' => $count,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privés
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Récupère l'Utilisateur depuis le corps JSON de la requête (slug + code).
     * Retourne un JsonResponse en cas d'erreur pour un early-return propre.
     */
    public function getUtilisateurByRequest(Request $request): mixed
    {
        $donnees = json_decode($request->getContent(), true);

        $slug = $donnees['slug'] ?? null;
        $code = $donnees['code'] ?? null;

        if (!$slug || !$code) {
            return $this->json(
                ['error' => 'Paramètres manquants (slug, code)'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $scout = $this->scoutRepository->findOneBy(['slug' => $slug]);
        if (!$scout) {
            return $this->json(
                ['error' => 'Profil introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['scout' => $scout]);
        if (!$utilisateur) {
            return $this->json(
                ['error' => 'Utilisateur introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $utilisateur;
    }

    /**
     * Sérialise une UtilisateurNotification pour l'API.
     */
    private function serialiserUtilisateurNotification(UtilisateurNotification $un): array
    {
        $notification = $un->getNotification();

        return [
            'id'            => $un->getId(),
            'titre'         => $notification->getTitre(),
            'message'       => $notification->getMessage(),
            'type'          => $notification->getType(),
            'icone'         => $notification->getIcone(),
            'urlAction'     => $notification->getUrlAction(),
            'libelleAction' => $notification->getLibelleAction(),
            'estLue'        => $un->isEstLue(),
            'creeLe'        => $un->getCreatedAt()?->format('c'),
            'lueLe'         => $un->getLuLe()?->format('c'),
        ];
    }
}
