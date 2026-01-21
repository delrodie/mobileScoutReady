<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use App\Services\DeviceManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/firebase-actions')]
class FirebaseActionsController extends AbstractController
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly DeviceManagerService $deviceManager,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/', name: 'app_firebase_actions_verify_device', methods: ['POST'])]
    public function verifyDevice(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null; // ❌ ERREUR CORRIGÉE: était $daya['phone']
        $otp = $data['otp'] ?? null;

        $this->logger->info('🔍 Tentative vérification OTP', [
            'phone' => $phoneNumber,
            'otp' => $otp
        ]);

        if (!$phoneNumber || !$otp) {
            $this->logger->error('❌ Données manquantes', [
                'phone' => $phoneNumber,
                'otp' => $otp
            ]);
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur) {
            $this->logger->error('❌ Utilisateur introuvable', ['phone' => $phoneNumber]);
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($this->deviceManager->verifyDeviceOtp($utilisateur, $otp)) {
            $this->logger->info('✅ OTP vérifié avec succès', ['phone' => $phoneNumber]);
            return $this->json([
                'status' => 'verified',
                'message' => 'Appareil vérifié avec succès'
            ]);
        }

        $this->logger->warning('⚠️ OTP invalide ou expiré', [
            'phone' => $phoneNumber,
            'otp_fourni' => $otp
        ]);

        return $this->json([
            'error' => 'Code OTP invalide ou expiré'
        ], Response::HTTP_UNAUTHORIZED);
    }

    #[Route('/approve-transfer', name: 'app_firebase_actions_approve_transfer', methods: ['POST'])]
    public function approveTransfer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null;
        $newDeviceId = $data['new_device_id'] ?? null;
        $newFcmToken = $data['new_fcm_token'] ?? null;

        $this->logger->info('🔍 Tentative approbation transfert', [
            'phone' => $phoneNumber,
            'new_device_id' => $newDeviceId
        ]);

        // ❌ ERREUR CORRIGÉE: manquait les ! devant $newDeviceId et $newFcmToken
        if (!$phoneNumber || !$newDeviceId || !$newFcmToken) {
            $this->logger->error('❌ Données manquantes pour transfert');
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur) {
            $this->logger->error('❌ Utilisateur introuvable', ['phone' => $phoneNumber]);
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($this->deviceManager->approveDeviceTransfer($utilisateur, $newDeviceId, $newFcmToken)) {
            $this->logger->info('✅ Transfert approuvé', ['phone' => $phoneNumber]);
            return $this->json([
                'status' => 'approved',
                'message' => 'Transfert approuvé'
            ]);
        }

        $this->logger->warning('⚠️ Échec approbation transfert', ['phone' => $phoneNumber]);
        return $this->json([
            'error' => "Échec de l'approbation"
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/deny/transfer', name: 'app_firebase_actions_deny_transfer', methods: ['POST'])]
    public function denyTransfer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null;

        $this->logger->info('🔍 Tentative refus transfert', ['phone' => $phoneNumber]);

        if (!$phoneNumber) {
            return $this->json(['error' => 'Numéro manquant'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur) {
            $this->logger->error('❌ Utilisateur introuvable', ['phone' => $phoneNumber]);
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $this->deviceManager->denyDeviceTransfer($utilisateur);

        $this->logger->info('✅ Transfert refusé', ['phone' => $phoneNumber]);

        return $this->json([
            'status' => 'denied',
            'message' => 'Transfert refusé'
        ]);
    }

    #[Route('/no-access/old/device', name: 'app_firebase_actions_no_access_old_device', methods: ['POST'])]
    public function noAccessOldDevice(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null;

        $this->logger->info('🔍 Demande sans accès ancien device', ['phone' => $phoneNumber]);

        if (!$phoneNumber) {
            return $this->json(['error' => 'Numéro manquant'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur) {
            $this->logger->error('❌ Utilisateur introuvable', ['phone' => $phoneNumber]);
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $result = $this->deviceManager->handleNoAccessToOldDevice($utilisateur);

        $this->logger->info('✅ Demande traitée', [
            'phone' => $phoneNumber,
            'status' => $result['status']
        ]);

        return $this->json($result);
    }
}
