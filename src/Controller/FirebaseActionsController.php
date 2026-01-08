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

#[Route('/firebase-actions')]
class FirebaseActionsController extends AbstractController
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly DeviceManagerService $deviceManager
    )
    {
    }

    #[Route('/', name: 'app_firebase_actions_verify_device', methods: ['POST'])]
    public function verifyDevice(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $daya['phone'] ?? null;
        $otp = $data['otp'] ?? null;

        if (!$phoneNumber || !$otp) {
            return $this->json(['error' => 'DOnnées manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $scout = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$scout) {
            return $this->json(['error' => "utilisateur introuvable"], Response::HTTP_NOT_FOUND);
        }

        if ($this->deviceManager->verifyDeviceOtp($scout, $otp)){
            return $this->json([
                'status' => 'verified',
                'message' => 'Appareil vérifié avec succès'
            ]);
        }

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

        if (!$phoneNumber || $newDeviceId || $newFcmToken){
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur){
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($this->deviceManager->approveDeviceTransfer($utilisateur, $newDeviceId, $newFcmToken)){
            return $this->json([
                'status' => 'approved',
                'message' => 'Transfert approuvé'
            ]);
        }

        return $this->json([
            'error' => "Echèc de l'approbation"
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/deny/transfer', name: 'app_firebase_actions_deny_transfer', methods: ['POST'])]
    public function denyTransfer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null;

        if (!$phoneNumber){
            return $this->json(['error' => 'Numéro manquant'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur){
            return $this->json(['error' => "Utilisateur introuvable"], Response::HTTP_NOT_FOUND);
        }

        $this->deviceManager->denyDeviceTransfer($utilisateur);
        return $this->json([
            'status' => 'denied',
            'message' => 'Transfert réfusé'
        ]);
    }

    #[Route('/no-access/old/device', name: 'app_firebase_actions_no_access_old_device', methods: ['POST'])]
    public function noAccessOldDevice(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null;

        if (!$phoneNumber){
            return $this->json(['error' => 'Numero maquant'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur){
            return $this->json(['error' => "Utilisateur introuvable"], Response::HTTP_NOT_FOUND);
        }

        $result = $this->deviceManager->handleNoAccessToOldDevice($utilisateur);

        return $this->json($result);
    }
}
