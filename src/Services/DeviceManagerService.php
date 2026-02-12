<?php

namespace App\Services;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des devices avec code PIN
 * VERSION SIMPLIFIÉE - Pas de Firebase, juste un PIN
 */
class DeviceManagerService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Gère l'authentification du device
     * Retourne le statut pour indiquer au frontend quoi faire
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
            'has_pin' => $utilisateur->hasPinCode()
        ]);

        // CAS 1: Utilisateur n'a PAS ENCORE de PIN → Créer le PIN
        if (!$utilisateur->hasPinCode()) {
            return $this->requestPinCreation($utilisateur, $deviceId, $devicePlatform, $deviceModel);
        }

        // CAS 2: Utilisateur n'a PAS de device enregistré → Premier device avec PIN existant
        if (!$utilisateur->getDeviceId()) {
            return $this->registerFirstDeviceWithPin($utilisateur, $deviceId, $devicePlatform, $deviceModel);
        }

        // CAS 3: MÊME DEVICE et vérifié → Connexion directe
        if ($utilisateur->getDeviceId() === $deviceId && $utilisateur->isDeviceVerified()) {
            $this->logger->info('✅ Même device vérifié - connexion directe', [
                'user_id' => $utilisateur->getId()
            ]);

            return [
                'status' => 'ok',
                'message' => 'Connexion autorisée',
                'requires_pin' => false
            ];
        }

        // CAS 4: NOUVEAU DEVICE → Demander le PIN
        return $this->handleNewDevice($utilisateur, $deviceId, $devicePlatform, $deviceModel);
    }

    /**
     * Demande la création d'un PIN (première connexion)
     */
    private function requestPinCreation(
        Utilisateur $utilisateur,
        string $deviceId,
        string $devicePlatform,
        string $deviceModel
    ): array {
        // Enregistrer temporairement le device (non vérifié)
        $utilisateur->setDeviceId($deviceId);
        $utilisateur->setDevicePlatform($devicePlatform);
        $utilisateur->setDeviceModel($deviceModel);
        $utilisateur->setDeviceVerified(false);

        $this->em->flush();

        $this->logger->info('📱 Demande création PIN', [
            'user_id' => $utilisateur->getId()
        ]);

        return [
            'status' => 'pin_creation_required',
            'message' => 'Veuillez créer un code PIN',
            'requires_pin' => false,
            'requires_pin_creation' => true
        ];
    }

    /**
     * Enregistre le premier device avec un PIN existant
     */
    private function registerFirstDeviceWithPin(
        Utilisateur $utilisateur,
        string $deviceId,
        string $devicePlatform,
        string $deviceModel
    ): array {
        // L'utilisateur a un PIN mais pas de device enregistré
        // Enregistrer le device et demander le PIN pour vérification

        $utilisateur->setDeviceId($deviceId);
        $utilisateur->setDevicePlatform($devicePlatform);
        $utilisateur->setDeviceModel($deviceModel);
        $utilisateur->setDeviceVerified(false);

        $this->em->flush();

        $this->logger->info('📱 Premier device avec PIN existant', [
            'user_id' => $utilisateur->getId()
        ]);

        return [
            'status' => 'pin_required',
            'message' => 'Entrez votre code PIN',
            'requires_pin' => true
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

        // Marquer comme non vérifié et demander le PIN
        $utilisateur->setDeviceVerified(false);
        $this->em->flush();

        return [
            'status' => 'new_device_pin_required',
            'message' => 'Nouveau device détecté. Entrez votre code PIN',
            'requires_pin' => true,
            'old_device' => [
                'platform' => $utilisateur->getDevicePlatform(),
                'model' => $utilisateur->getDeviceModel()
            ],
            'new_device' => [
                'id' => $newDeviceId,
                'platform' => $newDevicePlatform,
                'model' => $newDeviceModel
            ]
        ];
    }

    /**
     * Crée le code PIN pour l'utilisateur
     */
    public function createPin(Utilisateur $utilisateur, string $pin): array
    {
        // Validation
        if (!preg_match('/^\d{4}$/', $pin)) {
            return [
                'success' => false,
                'error' => 'Le PIN doit contenir exactement 4 chiffres'
            ];
        }

        $utilisateur->setPinCode($pin);
        $utilisateur->setDeviceVerified(true); // Premier device automatiquement vérifié

        $this->em->flush();

        $this->logger->info('✅ PIN créé avec succès', [
            'user_id' => $utilisateur->getId()
        ]);

        return [
            'success' => true,
            'message' => 'Code PIN créé avec succès'
        ];
    }

    /**
     * Vérifie le PIN et valide le device
     */
    public function verifyPin(Utilisateur $utilisateur, string $pin, string $newDeviceId): array
    {
        $this->logger->info('🔍 Vérification PIN', [
            'user_id' => $utilisateur->getId()
        ]);

        // Vérifier le PIN
        if (!$utilisateur->verifyPin($pin)) {
            $this->logger->warning('❌ PIN incorrect', [
                'user_id' => $utilisateur->getId()
            ]);

            return [
                'success' => false,
                'error' => 'Code PIN incorrect'
            ];
        }

        // ✅ PIN CORRECT → Changer le device et marquer comme vérifié
        $utilisateur->setDeviceId($newDeviceId);
        $utilisateur->setDeviceVerified(true);

        $this->em->flush();

        $this->logger->info('✅ PIN vérifié - device changé', [
            'user_id' => $utilisateur->getId(),
            'new_device' => $newDeviceId
        ]);

        return [
            'success' => true,
            'message' => 'Code PIN vérifié avec succès'
        ];
    }

    /**
     * Change le code PIN
     */
    public function changePin(Utilisateur $utilisateur, string $oldPin, string $newPin): array
    {
        // Vérifier l'ancien PIN
        if (!$utilisateur->verifyPin($oldPin)) {
            return [
                'success' => false,
                'error' => 'Ancien code PIN incorrect'
            ];
        }

        // Valider le nouveau PIN
        if (!preg_match('/^\d{4}$/', $newPin)) {
            return [
                'success' => false,
                'error' => 'Le nouveau PIN doit contenir exactement 4 chiffres'
            ];
        }

        // Changer le PIN
        $utilisateur->setPinCode($newPin);
        $this->em->flush();

        $this->logger->info('✅ PIN changé avec succès', [
            'user_id' => $utilisateur->getId()
        ]);

        return [
            'success' => true,
            'message' => 'Code PIN changé avec succès'
        ];
    }

    /**
     * Réinitialise le PIN (admin uniquement)
     */
    public function resetPin(Utilisateur $utilisateur): array
    {
        $utilisateur->setPinCode(null);
        $utilisateur->setDeviceVerified(false);
        $this->em->flush();

        $this->logger->warning('⚠️ PIN réinitialisé', [
            'user_id' => $utilisateur->getId()
        ]);

        return [
            'success' => true,
            'message' => 'PIN réinitialisé'
        ];
    }
}
