<?php

namespace App\Command;

use App\Repository\UtilisateurRepository;
use App\Services\DeviceManagerService;
use App\Services\FirebaseNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-otp-flow',
    description: 'Test le flux complet d\'envoi OTP pour un utilisateur',
)]
class TestOtpFlowCommand extends Command
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly DeviceManagerService $deviceManager,
        private readonly FirebaseNotificationService $firebaseService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('phone', InputArgument::REQUIRED, 'Numéro de téléphone');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $phoneNumber = $input->getArgument('phone');

        $io->title('🧪 Test du flux OTP complet');
        $io->section("Numéro: {$phoneNumber}");

        // 1. Trouver l'utilisateur
        $io->text('1️⃣  Recherche de l\'utilisateur...');
        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);

        if (!$utilisateur) {
            $io->error("❌ Utilisateur avec le numéro {$phoneNumber} introuvable");
            return Command::FAILURE;
        }

        $io->success("✅ Utilisateur trouvé: ID {$utilisateur->getId()}");

        // 2. Vérifier le token FCM
        $io->newLine();
        $io->text('2️⃣  Vérification du token FCM...');
        $fcmToken = $utilisateur->getFcmToken();

        if (!$fcmToken) {
            $io->error('❌ Token FCM absent en base de données');
            $io->warning([
                'L\'utilisateur n\'a pas de token FCM enregistré.',
                'Cela signifie que:',
                '- Soit il ne s\'est jamais connecté depuis l\'app mobile',
                '- Soit le token n\'a pas été envoyé au backend',
            ]);
            return Command::FAILURE;
        }

        $io->success([
            "✅ Token FCM présent",
            "Longueur: " . strlen($fcmToken) . " caractères",
            "Aperçu: " . substr($fcmToken, 0, 30) . "...",
        ]);

        // 3. Vérifier les infos device
        $io->newLine();
        $io->text('3️⃣  Informations du device...');

        $deviceInfo = [
            'Device ID' => $utilisateur->getDeviceId() ?? 'Non défini',
            'Platform' => $utilisateur->getDevicePlatform() ?? 'Non défini',
            'Model' => $utilisateur->getDeviceModel() ?? 'Non défini',
            'Vérifié' => $utilisateur->isDeviceVerified() ? 'Oui ✅' : 'Non ❌',
        ];

        $io->horizontalTable(
            array_keys($deviceInfo),
            [array_values($deviceInfo)]
        );

        // 4. Vérifier l'OTP existant
        $io->newLine();
        $io->text('4️⃣  État de l\'OTP actuel...');

        $currentOtp = $utilisateur->getDeviceVerificationOtp();
        $otpExpiry = $utilisateur->getDeviceVerificationOtpExpiry();

        if ($currentOtp && $otpExpiry) {
            $isExpired = new \DateTimeImmutable() > $otpExpiry;

            $io->table(
                ['Champ', 'Valeur'],
                [
                    ['Code OTP', $currentOtp],
                    ['Expiration', $otpExpiry->format('Y-m-d H:i:s')],
                    ['État', $isExpired ? '❌ Expiré' : '✅ Valide'],
                ]
            );
        } else {
            $io->warning('Aucun OTP généré pour cet utilisateur');
        }

        // 5. Tester l'envoi d'une notification
        $io->newLine();
        $io->section('5️⃣  Test d\'envoi de notification');

        $sendTest = $io->confirm('Voulez-vous envoyer une notification de test ?', false);

        if ($sendTest) {
            $io->text('📤 Envoi d\'une notification de test...');

            $testOtp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $result = $this->firebaseService->sendDeviceVerificationOtp(
                $fcmToken,
                $testOtp,
                $phoneNumber
            );

            if ($result) {
                $io->success([
                    '✅ Notification envoyée avec succès !',
                    "Code OTP de test: {$testOtp}",
                    'Vérifiez votre appareil.',
                ]);
            } else {
                $io->error([
                    '❌ Échec de l\'envoi de la notification',
                    'Vérifiez les logs pour plus de détails',
                ]);
                return Command::FAILURE;
            }
        }

        // 6. Résumé
        $io->newLine();
        $io->section('📊 Résumé');

        $summary = [];
        $summary[] = $utilisateur ? '✅ Utilisateur trouvé' : '❌ Utilisateur non trouvé';
        $summary[] = $fcmToken ? '✅ Token FCM présent' : '❌ Token FCM absent';
        $summary[] = $utilisateur->getDeviceId() ? '✅ Device ID enregistré' : '⚠️ Device ID manquant';
        $summary[] = $utilisateur->isDeviceVerified() ? '✅ Device vérifié' : '⚠️ Device non vérifié';

        $io->listing($summary);

        // 7. Recommandations
        $io->newLine();
        $io->section('💡 Recommandations');

        if (!$utilisateur->isDeviceVerified()) {
            $io->note([
                'Le device n\'est pas encore vérifié.',
                'L\'utilisateur doit entrer l\'OTP reçu par notification.',
            ]);
        }

        if (!$utilisateur->getDeviceId()) {
            $io->warning([
                'Device ID manquant.',
                'L\'utilisateur doit se reconnecter depuis l\'application mobile.',
            ]);
        }

        $io->success('✅ Test terminé');

        return Command::SUCCESS;
    }
}
