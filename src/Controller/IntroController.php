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
        // 1. GESTION DU GET (Affichage initial)
        if ($request->isMethod('GET')) {
            return $this->render('default/_search_phone.html.twig');
        }

        // 2. GESTION DU POST (Traitement AJAX/JSON)
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone'] ?? $request->request->get('phone');

        if (!$phoneNumber) {
            return new JsonResponse(['error' => 'Numéro de téléphone manquant'], Response::HTTP_BAD_REQUEST);
        }

        // Logique métier : Recherche des scouts
        $scouts = $scoutRepository->findBy(['telephone' => $phoneNumber]);

        // CAS : Aucun scout trouvé (RESTAURATION DE TA LOGIQUE)
        if (!$scouts) {
            // Si c'est une demande Turbo-Frame (pour l'affichage d'erreur inline)
            if ($request->headers->has('Turbo-Frame')) {
                return $this->render('default/_search_error.html.twig', [
                    'message' => "Numéro introuvable. Veuillez réessayer."
                ]);
            }

            // Si c'est un appel API/AJAX pur
            if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Content-Type', ''), 'application/json')) {
                return new JsonResponse([
                    'status' => 'not_found',
                    'message' => 'Numéro introuvable'
                ], Response::HTTP_NOT_FOUND);
            }

            // Fallback redirection
            return $this->redirectToRoute('app_inscription_choixregion');
        }

        // On prend le premier scout et son utilisateur
        $scout = $scouts[0];
        $utilisateur = $scout->getUtilisateur();

        if (!$utilisateur) {
            return new JsonResponse(['error' => 'Compte non activé'], Response::HTTP_FORBIDDEN);
        }

        // 3. VÉRIFICATION DU DEVICE (SMS OTP)
        $deviceId = $data['device_id'] ?? 'unknown';
        $platform = $data['device_platform'] ?? 'web';
        $model = $data['device_model'] ?? 'unknown';

        $authResult = $this->deviceManager->handleDeviceAuthentication(
            $utilisateur,
            $deviceId,
            $platform,
            $model
        );

        // 4. INJECTION DES DTO SI LA CONNEXION EST OK (RESTAURATION DE TES DONNÉES)
        if (isset($authResult['status']) && $authResult['status'] === 'ok') {
            $champs = $this->champActiviteRepository->findAll();
            $profilDTO = new ProfilDTO($utilisateur, $this->fonctionRepository);

            // On enrichit la réponse JSON avec tes objets métiers
            $authResult['profil'] = $profilDTO->profil;
            $authResult['profil_fonction'] = $profilDTO->profil_fonction;
            $authResult['profil_instance'] = $profilDTO->profil_instance;
            $authResult['champs_activite'] = ChampsDTO::listChamps($champs);

            // On ajoute une info pratique pour le JS (Parent ou non)
            $authResult['profil']['isParent'] = in_array('ROLE_PARENT', $utilisateur->getRole() ?? []);
        }

        // On renvoie TOUT en JSON pour Stimulus
        return new JsonResponse($authResult);
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
