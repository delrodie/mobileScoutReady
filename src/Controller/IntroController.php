<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\ChampsDTO;
use App\DTO\ProfilDTO;
use App\Entity\Utilisateur;
use App\Repository\ChampActiviteRepository;
use App\Repository\FonctionRepository;
use App\Repository\ScoutRepository;
use App\Repository\UtilisateurRepository;
use App\Services\DeviceManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/intro')]
class IntroController extends AbstractController
{
    public function __construct(
        private readonly FonctionRepository $fonctionRepository,
        private readonly ChampActiviteRepository $champActiviteRepository,
        private readonly DeviceManagerService $deviceManager,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/', name:'app_intro_synchro')]
    public function synchro(): Response
    {
        return $this->render('default/synchro.html.twig');
    }

    #[Route('/phone', name:'app_search_phone', methods: ['GET','POST'])]
    public function phone(Request $request, ScoutRepository $scoutRepository): Response
    {
        $session = $request->getSession();

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('_searchPhone', $request->get('_csrf_token'))) {

            $phoneRequest = $request->request->get('_phone_search');
            $scouts = $scoutRepository->findBy(['telephone' => $phoneRequest]);

            $this->logger->info("Le telephone saisi: {$phoneRequest}");

            $session->set('_phone_input', $phoneRequest);

            // Aucun compte trouvé
            if (!$scouts) {
                if ($request->headers->has('Turbo-Frame')) {
                    return $this->render('default/_search_error.html.twig', [
                        'message' => "Numéro introuvable. Veuillez réessayer."
                    ]);
                }

                if ($request->isXmlHttpRequest()){
                    return $this->json(['status' => 'nouveau'], Response::HTTP_OK);
                }
                return $this->redirectToRoute('app_inscription_choixregion');
            }

            // Si c'est un parent → choix du profil
            if ($scouts[0]->isPhoneParent()) {
                $session->set('_getScouts', $scouts);
            }

            // Si requête AJAX (depuis Stimulus)
            if ($request->isXmlHttpRequest()) {
                $this->logger->info("Avant d'entrée dans try");
                try {
                $scout = $scouts[0];
                $utilisateur = $scout->getUtilisateur();
                //$this->logger->info([$utilisateur]);
                $this->logger->info("A l'intérieur de try");

                // 🔥 Correction: Créer l'utilisateur s'il n'existe pas
                if (!$utilisateur) {
                    $utilisateur = new Utilisateur();
                    $utilisateur->setScout($scout);
                    $utilisateur->setTelephone($scout->getTelephone());
                    $this->entityManager->persist($utilisateur);
                    $this->entityManager->flush();
                }

                // 🔥 Récupérer les infos device depuis le frontend
                $deviceId = $request->request->get('device_id');
                $fcmToken = $request->request->get('fcm_token');
                $devicePlatform = $request->request->get('device_platform') ?? 'unknown';
                $deviceModel = $request->request->get('device_model') ?? 'unknown';

                $this->logger->info("device_plateforme: {$devicePlatform}, device_id: {$deviceId}, device_model: {$deviceModel}");

                // 🔥 Vérifier le device
                $deviceCheck = $this->deviceManager->handleDeviceAuthentication(
                    $utilisateur,
                    $deviceId,
                    $devicePlatform,
                    $deviceModel
                );

                $fonctions = $this->fonctionRepository->findAllByScout($scout->getId());
                $profilDTO = ProfilDTO::fromScout($fonctions);
                $champs = $this->champActiviteRepository->findAll();

                return $this->json([
                    'device_check' => $deviceCheck, // 🔥 Nouveau : statut du device
                    'profil' => $profilDTO->profil,
                    'profil_fonction' => $profilDTO->profil_fonction,
                    'profil_instance' => $profilDTO->profil_instance,
                    'champs_activite' => ChampsDTO::listChamps($champs)
                ]);
                } catch (\Throwable $e) {
                    $this->logger->info("Dans Catch {$e->getMessage()}");
                    return $this->json([
                        'error' => true,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // Cas fallback (classique)
            $session->set('_profil', $scouts[0]);
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('default/_search_phone.html.twig');
    }

    #[Route('/choix/profil', name: 'app_choix_profil', methods: ['GET','POST'])]
    public function choixProfil(Request $request): Response
    {
        $session = $request->getSession();
        $getScouts = $session->get('_getScouts');
        if (!$getScouts) return $this->redirectToRoute('app_search_phone');

        return $this->render('default/_choix_profil.html.twig', [
            'scouts' => $getScouts,
            'phone' => $session->get('_phone_input')
        ]);
    }

    #[Route('/profil/{slug}', name: 'app_profil_selectionne', methods: ['GET'])]
    public function selectProfil(Request $request, ScoutRepository $scoutRepository, string $slug): Response
    {
        $scout = $scoutRepository->findOneBy(['slug' => $slug]);
        if (!$scout){
            if ($request->isXmlHttpRequest()){
                return $this->json([
                    'error' => 'Profil non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            return $this->redirectToRoute('app_search_phone');
        }

        if ($request->isXmlHttpRequest()){
            $fonctions = $this->fonctionRepository->findAllByScout($scout->getId());
            $profilDTO = ProfilDTO::fromScout($fonctions);
            $champs = $this->champActiviteRepository->findAll();

            return $this->json([
                'profil' => $profilDTO->profil,
                'profil_fonction' => $profilDTO->profil_fonction,
                'profil_instance' => $profilDTO->profil_instance,
                'champs_activite' => ChampsDTO::listChamps($champs)
            ]);
        }

        $request->getSession()->set('profil', $scout);
        return $this->redirectToRoute('app_accueil');
    }

    // 🔥 Nouveau: Vérification OTP pour device
    #[Route('/verify-device', name: 'app_verify_device', methods: ['POST'])]
    public function verifyDevice(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null;
        $otp = $data['otp'] ?? null;

        if (!$phoneNumber || !$otp) {
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $scout = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$scout) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($this->deviceManager->verifyDeviceOtp($scout, $otp)) {
            return $this->json([
                'status' => 'verified',
                'message' => 'Appareil vérifié avec succès'
            ]);
        }

        return $this->json([
            'error' => 'Code OTP invalide ou expiré'
        ], Response::HTTP_UNAUTHORIZED);
    }

    // 🔥 Nouveau: Approuver le transfert de device
    #[Route('/approve-transfer', name: 'app_approve_device_transfer', methods: ['POST'])]
    public function approveTransfer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null;
        $newDeviceId = $data['new_device_id'] ?? null;
        $newFcmToken = $data['new_fcm_token'] ?? null;

        if (!$phoneNumber || !$newDeviceId || !$newFcmToken) {
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($this->deviceManager->approveDeviceTransfer($utilisateur, $newDeviceId, $newFcmToken)) {
            return $this->json([
                'status' => 'approved',
                'message' => 'Transfert approuvé'
            ]);
        }

        return $this->json([
            'error' => 'Échec de l\'approbation'
        ], Response::HTTP_BAD_REQUEST);
    }

    // 🔥 Nouveau: Refuser le transfert de device
    #[Route('/deny-transfer', name: 'app_deny_device_transfer', methods: ['POST'])]
    public function denyTransfer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null;

        if (!$phoneNumber) {
            return $this->json(['error' => 'Numéro manquant'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $this->deviceManager->denyDeviceTransfer($utilisateur);

        return $this->json([
            'status' => 'denied',
            'message' => 'Transfert refusé'
        ]);
    }

    // 🔥 Nouveau: Pas d'accès à l'ancien téléphone
    #[Route('/no-access-old-device', name: 'app_no_access_old_device', methods: ['POST'])]
    public function noAccessOldDevice(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? null;

        if (!$phoneNumber) {
            return $this->json(['error' => 'Numéro manquant'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phoneNumber]);
        if (!$utilisateur) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $result = $this->deviceManager->handleNoAccessToOldDevice($utilisateur);

        return $this->json($result);
    }
}
