<?php

namespace App\Services;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des devices avec SMS OTP
 * Version finale - 100% compatible avec le flux existant
 */
class DeviceManagerService
{
    private const OTP_EXPIRY_MINUTES = 10;
    private const ADMIN_PHONE = '0709321521';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Gère l'authentification du device
     * Retourne le statut pour que le frontend sache quoi faire
     */
    public function handleDeviceAuthentication(
        Utilisateur $utilisateur,
        string $deviceId,
        string $devicePlatform,
        string $deviceModel
    ): array {
        $this->logger->info('🔍 Vérification device', [
            'user_id' => $utilisateur->getId(),
            'device_id' => $deviceId,
            'current_device' => $utilisateur->getDeviceId(),
            'is_verified' => $utilisateur->isDeviceVerified()
        ]);

        // CAS 1: AUCUN DEVICE ENREGISTRÉ → Premier device
        if (!$utilisateur->getDeviceId()) {
            return $this->initializeFirstDevice($utilisateur, $deviceId, $devicePlatform, $deviceModel);
        }

        // CAS 2: MÊME DEVICE ET VÉRIFIÉ → Accès direct
        if ($utilisateur->getDeviceId() === $deviceId && $utilisateur->isDeviceVerified()) {
            $this->logger->info('✅ Device connu et vérifié', [
                'user_id' => $utilisateur->getId(),
                'device_id' => $deviceId
            ]);

            return [
                'status' => 'ok',
                'message' => 'Device vérifié',
                'requires_otp' => false
            ];
        }

        // CAS 3: MÊME DEVICE MAIS NON VÉRIFIÉ → Renvoyer OTP
        if ($utilisateur->getDeviceId() === $deviceId && !$utilisateur->isDeviceVerified()) {
            return $this->requestOtpVerification($utilisateur);
        }

        // CAS 4: NOUVEAU DEVICE → Demander vérification
        return $this->handleNewDevice($utilisateur, $deviceId, $devicePlatform, $deviceModel);
    }

    /**
     * Initialise le premier device (jamais connecté)
     * Génère un OTP que le frontend enverra par SMS Firebase
     */
    private function initializeFirstDevice(
        Utilisateur $utilisateur,
        string $deviceId,
        string $devicePlatform,
        string $deviceModel
    ): array {
        $otp = $this->generateOtp();

        // Enregistrer le device
        $utilisateur->setDeviceId($deviceId);
        $utilisateur->setDevicePlatform($devicePlatform);
        $utilisateur->setDeviceModel($deviceModel);

        // Stocker l'OTP pour validation
        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+' . self::OTP_EXPIRY_MINUTES . ' minutes')
        );
        $utilisateur->setDeviceVerified(false);

        $this->em->flush();

        $this->logger->info('📱 Premier device initialisé', [
            'user_id' => $utilisateur->getId(),
            'device_id' => $deviceId,
            'phone' => $utilisateur->getTelephone(),
            'otp_generated' => '***' // Ne pas logger l'OTP complet
        ]);

        return [
            'status' => 'verification_required',
            'message' => 'Premier device - Vérification requise',
            'requires_otp' => true,
            'phone' => $utilisateur->getTelephone(),
            'otp_expiry' => self::OTP_EXPIRY_MINUTES,
            // En dev: décommenter pour voir l'OTP dans les logs
            // 'dev_otp' => $otp
        ];
    }

    /**
     * Demande une vérification OTP pour un device non vérifié
     */
    private function requestOtpVerification(Utilisateur $utilisateur): array
    {
        // Vérifier si l'OTP est encore valide
        if ($utilisateur->getDeviceVerificationOtp()
            && $utilisateur->getDeviceVerificationOtpExpiry()
            && new \DateTimeImmutable() < $utilisateur->getDeviceVerificationOtpExpiry()) {

            $this->logger->info('♻️ OTP encore valide', [
                'user_id' => $utilisateur->getId()
            ]);

            return [
                'status' => 'verification_required',
                'message' => 'Vérification en attente',
                'requires_otp' => true,
                'phone' => $utilisateur->getTelephone(),
                'otp_expiry' => self::OTP_EXPIRY_MINUTES
            ];
        }

        // Générer un nouvel OTP
        $otp = $this->generateOtp();

        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+' . self::OTP_EXPIRY_MINUTES . ' minutes')
        );

        $this->em->flush();

        $this->logger->info('🔄 Nouvel OTP généré', [
            'user_id' => $utilisateur->getId()
        ]);

        return [
            'status' => 'verification_required',
            'message' => 'Nouveau code requis',
            'requires_otp' => true,
            'phone' => $utilisateur->getTelephone(),
            'otp_expiry' => self::OTP_EXPIRY_MINUTES
        ];
    }

    /**
     * Gère la connexion depuis un nouveau device
     */
    private function handleNewDevice(
        Utilisateur $utilisateur,
        string $newDeviceId,
        string $newDevicePlatform,
        string $newDeviceModel
    ): array {
        $this->logger->warning('⚠️ Nouveau device détecté', [
            'user_id' => $utilisateur->getId(),
            'old_device' => $utilisateur->getDeviceId(),
            'new_device' => $newDeviceId,
            'old_platform' => $utilisateur->getDevicePlatform(),
            'new_platform' => $newDevicePlatform
        ]);

        // Générer un OTP pour le nouveau device
        $otp = $this->generateOtp();

        // Sauvegarder le pending device
        $utilisateur->setPendingDeviceId($newDeviceId);
        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+' . self::OTP_EXPIRY_MINUTES . ' minutes')
        );

        $this->em->flush();

        return [
            'status' => 'new_device',
            'message' => 'Nouveau device détecté',
            'requires_otp' => true,
            'phone' => $utilisateur->getTelephone(),
            'otp_expiry' => self::OTP_EXPIRY_MINUTES,
            'old_device' => [
                'platform' => $utilisateur->getDevicePlatform(),
                'model' => $utilisateur->getDeviceModel()
            ],
            'new_device' => [
                'platform' => $newDevicePlatform,
                'model' => $newDeviceModel
            ]
        ];
    }

    /**
     * Vérifie l'OTP et valide le device
     * MÉTHODE PRINCIPALE appelée après que Firebase ait envoyé le SMS
     */
    public function verifyDeviceOtp(Utilisateur $utilisateur, string $otp): bool
    {
        $this->logger->info('🔍 Vérification OTP', [
            'user_id' => $utilisateur->getId(),
            'has_otp' => !empty($utilisateur->getDeviceVerificationOtp()),
            'otp_expired' => $utilisateur->getDeviceVerificationOtpExpiry()
                ? (new \DateTimeImmutable() > $utilisateur->getDeviceVerificationOtpExpiry())
                : true
        ]);

        // Vérifier la validité de l'OTP
        if (!$utilisateur->isDeviceOptValid($otp)) {
            $this->logger->warning('❌ OTP invalide ou expiré', [
                'user_id' => $utilisateur->getId()
            ]);
            return false;
        }

        // ✅ OTP VALIDE → Marquer le device comme vérifié
        $utilisateur->setDeviceVerified(true);
        $utilisateur->setDeviceVerificationOtp(null);
        $utilisateur->setDeviceVerificationOtpExpiry(null);

        // Si c'était un pending device, l'activer
        if ($utilisateur->getPendingDeviceId()) {
            $oldDeviceId = $utilisateur->getDeviceId();
            $utilisateur->setDeviceId($utilisateur->getPendingDeviceId());
            $utilisateur->setPendingDeviceId(null);

            $this->logger->info('🔄 Device changé', [
                'user_id' => $utilisateur->getId(),
                'old_device' => $oldDeviceId,
                'new_device' => $utilisateur->getDeviceId()
            ]);
        }

        $this->em->flush();

        $this->logger->info('✅ Device vérifié avec succès', [
            'user_id' => $utilisateur->getId(),
            'device_id' => $utilisateur->getDeviceId(),
            'verified' => true
        ]);

        return true;
    }

    /**
     * Renvoie un nouvel OTP
     */
    public function resendOtp(Utilisateur $utilisateur): array
    {
        $otp = $this->generateOtp();

        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+' . self::OTP_EXPIRY_MINUTES . ' minutes')
        );
        $this->em->flush();

        $this->logger->info('🔄 OTP renvoyé', [
            'user_id' => $utilisateur->getId(),
            'phone' => $utilisateur->getTelephone()
        ]);

        return [
            'success' => true,
            'message' => 'Nouveau code généré',
            'phone' => $utilisateur->getTelephone(),
            'otp_expiry' => self::OTP_EXPIRY_MINUTES
        ];
    }

    /**
     * Gère le cas où l'utilisateur n'a plus accès à l'ancien téléphone
     */
    public function handleNoAccessToOldDevice(Utilisateur $utilisateur): array
    {
        $otp = $this->generateOtp();

        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+24 hours') // 24h pour laisser le temps
        );
        $this->em->flush();

        $this->logger->warning('⚠️ Demande sans accès ancien device', [
            'user_id' => $utilisateur->getId(),
            'phone' => $utilisateur->getTelephone()
        ]);

        // TODO: Notifier un admin si nécessaire
        // $this->notifyAdmin($utilisateur, $otp);

        return [
            'status' => 'otp_sent',
            'message' => 'Un code OTP va être envoyé',
            'phone' => $utilisateur->getTelephone(),
            'otp_expiry' => 1440 // 24h en minutes
        ];
    }

    /**
     * Approuve le transfert vers un nouveau device (legacy - pour compatibilité)
     */
    public function approveDeviceTransfer(Utilisateur $utilisateur, string $newDeviceId, string $newFcmToken): bool
    {
        if ($utilisateur->getPendingDeviceId() !== $newDeviceId) {
            $this->logger->error('Device ID ne correspond pas', [
                'pending' => $utilisateur->getPendingDeviceId(),
                'provided' => $newDeviceId
            ]);
            return false;
        }

        $utilisateur->setDeviceId($newDeviceId);
        $utilisateur->setDeviceVerified(true);
        $utilisateur->setPendingDeviceId(null);
        $utilisateur->setDeviceVerificationOtp(null);
        $utilisateur->setDeviceVerificationOtpExpiry(null);

        // Sauvegarder le FCM token si fourni (pour compatibilité)
        if ($newFcmToken) {
            $utilisateur->setFcmToken($newFcmToken);
        }

        $this->em->flush();

        $this->logger->info('✅ Transfert approuvé', [
            'user_id' => $utilisateur->getId(),
            'new_device_id' => $newDeviceId
        ]);

        return true;
    }

    /**
     * Refuse le transfert de device
     */
    public function denyDeviceTransfer(Utilisateur $utilisateur): void
    {
        $utilisateur->setPendingDeviceId(null);
        $utilisateur->setDeviceVerificationOtp(null);
        $utilisateur->setDeviceVerificationOtpExpiry(null);
        $this->em->flush();

        $this->logger->warning('❌ Transfert refusé', [
            'user_id' => $utilisateur->getId()
        ]);
    }

    /**
     * Génère un code OTP aléatoire à 6 chiffres
     */
    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Debug: Obtient l'OTP actuel (À SUPPRIMER EN PRODUCTION)
     */
    public function getCurrentOtp(Utilisateur $utilisateur): ?string
    {
        if ($utilisateur->getDeviceVerificationOtpExpiry()
            && new \DateTimeImmutable() <= $utilisateur->getDeviceVerificationOtpExpiry()) {
            return $utilisateur->getDeviceVerificationOtp();
        }

        return null;
    }
}
