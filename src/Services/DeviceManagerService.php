<?php

namespace App\Services;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Version SIMPLIFIÉE pour SMS OTP
 * Pas besoin de Firebase SDK PHP - tout se passe côté client !
 */
class DeviceManagerService
{
    private const OTP_EXPIRY_MINUTES = 10;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Vérifie si le device est autorisé ou nécessite une validation SMS
     */
    public function handleDeviceAuthentication(
        Utilisateur $utilisateur,
        string $deviceId,
        string $devicePlatform,
        string $deviceModel
    ): array {
        // Cas 1: Utilisateur n'a aucun device enregistré (Nouvelle installation)
        if (!$utilisateur->getDeviceId()) {
            return [
                'status' => 'new_device',
                'message' => 'Premier enregistrement requis',
                'requires_otp' => true,
                'phone' => $utilisateur->getTelephone()
            ];
        }

        // Cas 2: Le device ID correspond à celui enregistré
        if ($utilisateur->getDeviceId() === $deviceId ) {
            $this->logger->info('✅ Même device vérifié', [
                'user_id' => $utilisateur->getId()
            ]);

            return [
                'status' => 'ok',
                'message' => 'Connexion autorisée',
                'requires_otp' => false,
                'phone' => $utilisateur->getTelephone()
            ];
        }

        // Cas 3: Changement de device detecté
        $this->logger->warning("Tentative de connexion depuis un nouveau device",[
            'user_id' => $utilisateur->getId(),
            'old_device' => $utilisateur->getDeviceId(),
            'new_device' => $deviceId
        ]);

        return [
            'status' => 'new_device',
            'message' => "Validation par SMS requise pour ce nouveau terminal",
            'requires_otp' => true,
            'phone' => $utilisateur->getTelephone()
        ];
    }

    /**
     * Enregistre officiellement le device une fois que le SMS a été valid" coté client
     */
    public function confirmDeviceRegistration(Utilisateur $utilisateur, string $deviceId, string $platform, string $model): void
    {
        $utilisateur->setDeviceId($deviceId);
        $utilisateur->setDevicePlatform($platform);
        $utilisateur->setDeviceModel($model);
        $utilisateur->setDeviceVerified(true);
        $utilisateur->setLastConnectedAt(new \DateTimeImmutable());

        $this->em->flush();

        $this->logger->info("Nouveau device enregistré avec succès",[
            'user' => $utilisateur->getTelephone(),
            'device_id' => $deviceId
        ]);
    }

    /**
     * Enregistre le premier device
     * Note: L'envoi du SMS se fait côté client avec Firebase
     */
    private function registerFirstDevice(
        Utilisateur $utilisateur,
        string $deviceId,
        string $devicePlatform,
        string $deviceModel
    ): array {
        // Générer un OTP pour validation serveur
        $otp = $this->generateOtp();

        $utilisateur->setDeviceId($deviceId);
        $utilisateur->setDevicePlatform($devicePlatform);
        $utilisateur->setDeviceModel($deviceModel);
        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+' . self::OTP_EXPIRY_MINUTES . ' minutes')
        );
        $utilisateur->setDeviceVerified(false);

        $this->em->flush();

        $this->logger->info("📱 Premier device enregistré", [
            'user_id' => $utilisateur->getId(),
            'device_id' => $deviceId,
            'phone' => $utilisateur->getTelephone()
        ]);

        return [
            'status' => 'verification_required',
            'message' => 'Code OTP requis',
            'requires_otp' => true,
            'phone' => $utilisateur->getTelephone(),
            'otp_expiry' => self::OTP_EXPIRY_MINUTES,
            // ⚠️ En dev, on peut retourner l'OTP (À SUPPRIMER EN PROD)
            // 'dev_otp' => $otp
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
            'new_device' => $newDeviceId
        ]);

        // Générer un nouvel OTP
        $otp = $this->generateOtp();

        $utilisateur->setPendingDeviceId($newDeviceId);
        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+' . self::OTP_EXPIRY_MINUTES . ' minutes')
        );
        $utilisateur->setDeviceVerified(false);

        $this->em->flush();

        return [
            'status' => 'new_device',
            'message' => 'Nouveau device détecté. Un code OTP va être envoyé par SMS',
            'requires_otp' => true,
            'phone' => $utilisateur->getTelephone(),
            'otp_expiry' => self::OTP_EXPIRY_MINUTES,
            'show_no_access_option' => true
        ];
    }

    /**
     * Valide l'OTP de vérification du device
     * ✅ MÉTHODE PRINCIPALE - Appelée après vérification Firebase côté client
     */
    public function verifyDeviceOtp(Utilisateur $utilisateur, string $otp): bool
    {
        $this->logger->info('🔍 Vérification OTP serveur', [
            'user_id' => $utilisateur->getId()
        ]);

        // Vérifier que l'OTP correspond et n'a pas expiré
        if (!$utilisateur->isDeviceOptValid($otp)) {
            $this->logger->warning('⚠️ OTP invalide ou expiré', [
                'user_id' => $utilisateur->getId()
            ]);
            return false;
        }

        // ✅ MARQUER LE DEVICE COMME VÉRIFIÉ
        $utilisateur->setDeviceVerified(true);
        $utilisateur->setDeviceVerificationOtp(null);
        $utilisateur->setDeviceVerificationOtpExpiry(null);

        // Si c'était un pending device, l'activer
        if ($utilisateur->getPendingDeviceId()) {
            $utilisateur->setDeviceId($utilisateur->getPendingDeviceId());
            $utilisateur->setPendingDeviceId(null);
        }

        $this->em->flush();

        $this->logger->info('✅ Device vérifié avec succès', [
            'user_id' => $utilisateur->getId(),
            'device_id' => $utilisateur->getDeviceId()
        ]);

        return true;
    }

    /**
     * Renvoie un nouvel OTP
     * Note: L'envoi du SMS se fait côté client
     */
    public function resendOtp(Utilisateur $utilisateur): array
    {
        $otp = $this->generateOtp();

        $utilisateur->setDeviceVerificationOtp($otp);
        $utilisateur->setDeviceVerificationOtpExpiry(
            (new \DateTimeImmutable())->modify('+' . self::OTP_EXPIRY_MINUTES . ' minutes')
        );
        $this->em->flush();

        $this->logger->info('🔄 OTP regénéré', [
            'user_id' => $utilisateur->getId()
        ]);

        return [
            'success' => true,
            'message' => 'Nouveau code généré',
            'otp_expiry' => self::OTP_EXPIRY_MINUTES,
            // ⚠️ En dev uniquement
            // 'dev_otp' => $otp
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
            (new \DateTimeImmutable())->modify('+24 hours')
        );
        $this->em->flush();

        $this->logger->warning("⚠️ Demande sans accès ancien device", [
            'user_phone' => $utilisateur->getTelephone()
        ]);

        return [
            'status' => 'otp_sent',
            'message' => 'Un code OTP va être envoyé par SMS',
            'phone' => $utilisateur->getTelephone(),
            'otp_expiry' => 1440 // 24h en minutes
        ];
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

        $this->logger->warning("❌ Transfert refusé", [
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
     * Obtient l'OTP actuel (pour debug uniquement)
     * ⚠️ À SUPPRIMER EN PRODUCTION
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
