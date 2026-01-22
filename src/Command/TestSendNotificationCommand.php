<?php

namespace App\Command;

use App\Repository\UtilisateurRepository;
use App\Services\FirebaseNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-send-notification',
    description: "Test d'envoie de notification ",
)]
class TestSendNotificationCommand extends Command
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly FirebaseNotificationService $firebaseService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('phone', InputArgument::REQUIRED, 'Numéro de téléphone')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $phoneNumber = $input->getArgument('phone');

        // Recherche de l'utilisateur
        $io->text("Recherche de l'utilisateur....");
        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);

        if (!$utilisateur) {
            $io->error("le numero {$phoneNumber} ne correspond à aucun utilisateur");
            return Command::FAILURE;
        }

        $io->success("Utilisateur trouvé : ID {$utilisateur->getId()}");

        // Verification du token FCM
        $io->newLine();
        $io->text("Vérification du token FCM...");
        $fcmToken = $utilisateur->getFcmToken();

        if (!$fcmToken){
            $io->error("Token FCM absent en base de données");
            return Command::FAILURE;
        }

        // Test d'envoi de notification
        $io->newLine();
        $io->section("Test d'envoi de notification");
        $sendTest = $io->confirm("Voulez-vous envoyer une notification de test ?", false);

        if ($sendTest){
            $io->text("Envoi d'une notification test ...");

            $result = $this->firebaseService->sendNotification(
                $fcmToken,
                "Akwaba ScoutReady",
                "Ceci est un test d'envoie de message. Si tu recois c'est que tu as un téléphone portable",
                [
                    'type' => "Envoi test"
                ]
            );

            if ($result){
                $io->success("Notification envoyée avec succès, verifiez votre appareil");
            }else{
                $io->error([
                    'Echec',
                    'Verifiez les logs'
                ]);
                return Command::FAILURE;
            }
        }

        $io->success('Test envoye avec succès!');

        return Command::SUCCESS;
    }
}
