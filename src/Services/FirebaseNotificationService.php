<?php

namespace App\Services;

use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;

readonly class FirebaseNotificationService
{

    public function __construct(
        private Messaging       $messaging,
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * Envoie une notification OTP de vérification de device
     *
     * @param string $fcmToken
     * @param string $otp
     * @param string $phoneNumber
     * @return bool
     * @throws FirebaseException
     * @throws MessagingException
     */

    public function sendDeviceVerificationOtp(string $fcmToken, string $otp, string $phoneNumber): bool
    {

        try{
            $message = CloudMessage::new()
                ->withNotification(
                    Notification::create(
                        '🔐 Code de vérification',
                        "Votre code OTP : {$otp}"
                    )
                )
                ->withData([
                    'type' => 'device_verification',
                    'otp' => $otp,
                    'phone' => $phoneNumber,
                    'timestamp' => time()
                ])
            ;
            $this->messaging->send($message);
            $this->logger->info('OTP envoyé via Firebase', ['phone' => $phoneNumber]);
            return true;
        } catch (\Exception $e){
            $this->logger->error("Erreur envoi OTP Firebase", [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return  false;
        }
    }

    /**
     * Envoie une notification pour demander le transfer de device
     */
    public function sendDeviceTransferRequest(
        string $oldFcmTpken,
        string $phoneNumber,
        string $newDeviceModel,
        string $newDevicePlatform
    )
    {
        try{
            $message = CloudMessage::new()
                ->withNotification(
                    Notification::create(
                        '📱 Nouvelle connexion détectée',
                        "Quelqu'un tente de se connecter depuis un {$newDevicePlatform} ({$newDeviceModel}). Autoriser ?"
                    )
                )
                ->withData([
                    'type' => 'device_transfer_request',
                    'phone' => $phoneNumber,
                    'new_device_model' => $newDeviceModel,
                    'new_device_platform' => $newDevicePlatform,
                    'action_required' => 'approuve_or_deny',
                    'timestamp' => time()
                ])
                ;
            $this->messaging->send($message);
            $this->logger->info("Demande de transfert envoyée", ['phone' => $phoneNumber]);
            return true;
        } catch (\Exception $e){
            $this->logger->error("Erreur demande transfert", [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Notifie l'administrateur qu'un utilisateur demande un transfert sans accès à l'ancien téléphone
     */
    public function notifyAdminDeviceTransferNoAccess(
        string $adminFcmToken,
        string $userPhone,
        string $otp
    ): bool
    {
        try{
            $message = CloudMessage::new()
                ->withNotification(
                    Notification::create(
                        '⚠️ Transfert de compte sans accès',
                        "L'utilisateur {$userPhone} demande un transfert. Code OTP : {$otp}"
                    )
                )
                ->withData([
                    'type' => 'admin_device_transfer',
                    'user_phone' => $userPhone,
                    'otp' => $otp,
                    'timestamp' => time(),
                    'priority' => 'high'
                ])
                ;
            $this->messaging->send($message);
            $this->logger->info("Admin notifié pour transfert",['user_phone' => $userPhone]);
            return true;
        } catch (\Exception $e){
            $this->logger->error("Erreur notification admin",[
                'user_phone' => $userPhone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notifie par SMS (via Firebase) si push notification échoue
     * @param string $phoneNumber
     * @param string $otp
     */
    public function sendOtpViaSms(string $phoneNumber, string $otp): false
    {
        $this->logger->warning("SMS OTP à envoyer (non implementé)",[
            'phone' => $phoneNumber,
            'otp' => $otp
        ]);
        return false;
    }

    /**
     * Envoie une notification générique
     */
    public function sendNotification(string $fcmToken, string $title, string $body, array $data= []): bool
    {
        try{
            $message = CloudMessage::new()
                ->toToken($fcmToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);

            $this->logger->info('Notification envoyée avec succès', [
//                'phone' => $user->getTelephone(),
                'token' => substr($fcmToken, 0, 20) . '...'
            ]);

            return true;
        } catch(\Exception $e){
            $this->logger->error("Erreur envoi notification", ['error' => $e->getMessage()]);
            return false;
        }
    }

//    private function messaging()
//    {
//        $factory = (new Factory())->withServiceAccount(dirname(__DIR__).'/config/firebase.credentials.json');
//        return $factory->createMessaging();
//    }
}
