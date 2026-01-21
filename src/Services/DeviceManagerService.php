<?php

namespace App\Services;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DeviceManagerService
{
    private const OTP_EXPIRY_MINUTES = 10;
    private const ADMIN_PHONE = '0709321521';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly FirebaseNotificationService $firebaseService,
        private readonly LoggerInterface $logger
    )
    {
    }

    /**
     *Gère la connexion avec vérification du device
     */
    public function handleDeviceAuthentication(
        Utilisateur $utilisateur,
        string $deviceId,
        string $fcmToken,
        string $devicePlatform,
        string $deviceModel
    ): array
    {
        // Cas 1: Premier device (aucun device enregistré)
        if (!$utilisateur->getDeviceId()){
            return $this->registerFirstDevice($utilisateur, $deviceId, $fcmToken, $devicePlatform, $deviceModel);
        }

        // Cas 2: Même device -> accès direct
        if ($utilisateur->getDeviceId() === $deviceId && $utilisateur->isDeviceVerified()){
            $this->updateFcmToken($utilisateur, $fcmToken);
            return [
                'status' => 'ok',
                'message' => 'Connexion autorisée',
                'requires_otp' => false
            ];
        }

        // Cas 3: Nouveau device -> demander validation
        return $this->handleNewDevice($utilisateur, $deviceId, $fcmToken, $devicePlatform, $deviceModel);
    }

    /**
     * Enregistre le premier device de l'utilisateur
     */
    private function registerFirstDevice(
        Utilisateur $utilisateur,
        string $deviceId,
        string $fcmToken,
        string $devicePlatform,
        string $deviceModel
    ): array
    {
        $otp = $this->generateOtp();

        $utilisateur->setDeviceId($deviceId);
        $utilisateur->setFcmToken($fcmToken);
        $utilisateur->setDevicePlatform($devicePlatform);
        $utilisateur->setDeviceModel($deviceModel);
        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+' . self::OTP_EXPIRY_MINUTES . ' minutes')
        );
        $utilisateur->setDeviceVerified(false);

        $this->em->flush();

        $this->firebaseService->sendDeviceVerificationOtp($fcmToken, $otp, $utilisateur->getTelephone());

        $this->logger->info("Premier device enregistré",[
            'user_id' => $utilisateur->getId(),
            'device_id' => $deviceId,
        ]);

        return [
            'status' => 'verification_required',
            'message' => 'Code OTP envoyé sur votre appareil',
            'requires_otp' => true,
            'otp_expiry' => self::OTP_EXPIRY_MINUTES
        ];
    }

    /**
     * Gère la connexion depuis un nouveau device
     */
    private function handleNewDevice(
        Utilisateur $utilisateur,
        string $newDeviceId,
        string $newFcmToken,
        string $newDevicePlatform,
        string $newDeviceModel
    ): array
    {
        // Sauvegarde de l'ancien token pour modifier l'ancien device
        $oldFcmToken = $utilisateur->getFcmToken();
        if ($oldFcmToken){
            $utilisateur->setPreviousFcmToken($oldFcmToken);
            $utilisateur->setPendingDeviceId($newDeviceId);
            $this->em->flush();

            // Notifier l'ancien device
            $this->firebaseService->sendDeviceTransferRequest(
                $oldFcmToken,
                $utilisateur->getTelephone(),
                $newDeviceModel,
                $newDevicePlatform
            );

            $this->logger->info("Demande de transfert envoyée à l'ancien device",[
                'user_id' => $utilisateur->getId(),
                'old_device' => $utilisateur->getDeviceId(),
                'new_device' => $newDeviceId,
            ]);

            return [
                'status' => 'new_device',
                'message' => "Une notification a été encoyée à votre ancien appareil pour valider le transfert",
                'requires_approval' => true,
                'show_no_access_option' => true
            ];
        }

        // Si pas d'ancien token, procéder comme nouveau device
        return $this->registerFirstDevice($utilisateur, $newDeviceId, $newFcmToken, $newDevicePlatform, $newDeviceModel );
    }

    public function verifyDeviceOtp(Utilisateur $utilisateur, string $otp): bool
    {
        if (!$utilisateur->isDeviceOptValid($otp)){
            return false;
        }
        $utilisateur->setDeviceVerified(true);
        $utilisateur->setDeviceVerificationOtp(null);
        $utilisateur->setDeviceVerificationOtpExpiry(null);
        $this->em->flush();

        $this->logger->info("Device vérifié avec succès",[
            'user_id' => $utilisateur->getId(),
            'device_id' => $utilisateur->getDeviceId()
        ]);

        return true;
    }

    /**
     * Approuve le transfert vers un nouveau device
     */
    public function approveDeviceTransfer(Utilisateur $utilisateur, string $newDeviceId, string $newFcmToken): bool
    {
        if ($utilisateur->getPendingDeviceId() !== $newDeviceId){
            return false;
        }
            $utilisateur->setDeviceId($newDeviceId);
            $utilisateur->setFcmToken($newFcmToken);
            $utilisateur->setDeviceVerified(true);
            $utilisateur->setPendingDeviceId(null);
            $utilisateur->setPreviousFcmToken(null);
            $this->em->flush();

            $this->logger->info("Transfert de device approuvé",[
                'user_id' => $utilisateur->getId(),
                'new_device_id' => $newDeviceId
            ]);

            return true;

    }

    /**
     * Gère le cas où l'utilisateur n'a plus accès à l'ancien téléphone
     * @param Utilisateur $utilisateur
     */
    public function handleNoAccessToOldDevice(Utilisateur $utilisateur): array
    {
        $otp = $this->generateOtp();

        // Générer OTP pour validation admin
        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+24 hours')
        );
        $this->em->flush();

        // Récupérer le toen FCM de l'admin
        $admin = $this->utilisateurRepository->findOneBy(['telephone' => self::ADMIN_PHONE]);
        if ($admin && $admin->getFcmToken()){
            $this->firebaseService->notifyAdminDeviceTransferNoAccess(
                $admin->getFcmToken(),
                $utilisateur->getTelephone(),
                $otp
            );

            $this->logger->warning("Demande de transfert sans accès - Admin notifié",[
                'user_phone' => $utilisateur->getTelephone()
            ]);

            return [
                'status' => 'admin_notified',
                'message' => "Un administrateur a été notifié. VOus recevrez un code de validation sous peu.",
                'otp_via_admin' => true
            ];
        }

        // Fallback: envoyer SM à l'utilisateur
        $this->firebaseService->sendOtpViaSms($utilisateur->getTelephone(), $otp);

        return [
            'status' => "otp_sent",
            'message' => 'Un code OTP vous a été envoyé par SMS',
            'otp_expiry' => 1440
        ];
    }

    /**
     * Mise à jour du token FCM
     *
     * @param Utilisateur $utilisateur
     * @param string $fcmToken
     * @return void
     */
    private function updateFcmToken(Utilisateur $utilisateur, string $fcmToken): void
    {
        if ($utilisateur->getFcmToken() !== $fcmToken){
            $utilisateur->setFcmToken($fcmToken);
            $this->em->flush();
        }
    }

    /**
     * Génération aléatoire de code OTP
     */
    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Refus du transfert de device (appel depuis l'ancien device)
     *
     * @param Utilisateur $utilisateur
     * @return void
     */
    public function denyDeviceTransfer(Utilisateur $utilisateur): void
    {
        $utilisateur->setPendingDeviceId(null);
        $this->em->flush();

        $this->logger->warning("Transfert de device réfusé",[
            'user_id' => $utilisateur->getId(),
        ]);
    }
}
