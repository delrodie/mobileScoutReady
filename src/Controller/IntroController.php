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

            $this->logger->info("📞 Téléphone saisi: {$phoneRequest}");

            $session->set('_phone_input', $phoneRequest);

            // ❌ AUCUN COMPTE TROUVÉ → Inscription
            if (!$scouts) {
                if ($request->headers->has('Turbo-Frame')) {
                    return $this->render('default/_search_error.html.twig', [
                        'message' => "Numéro introuvable. Veuillez réessayer."
                    ]);
                }

                if ($request->isXmlHttpRequest()){
                    return $this->json([
                        'status' => 'new_user',
                        'message' => 'Aucun compte trouvé'
                    ], Response::HTTP_OK);
                }

                return $this->redirectToRoute('app_inscription_choixregion');
            }

            // 👨‍👩‍👧 PARENT → Choix du profil
            if ($scouts[0]->isPhoneParent()) {
                $session->set('_getScouts', $scouts);

                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'status' => 'ok',
                        'profil' => ['isParent' => true],
                        'message' => 'Choix du profil requis'
                    ]);
                }
            }

            // 🔐 REQUÊTE AJAX → Vérification device + données profil
            if ($request->isXmlHttpRequest()) {
                try {
                    $scout = $scouts[0];
                    $utilisateur = $scout->getUtilisateur();

                    // ✅ CRÉER L'UTILISATEUR S'IL N'EXISTE PAS
                    if (!$utilisateur) {
                        $utilisateur = new Utilisateur();
                        $utilisateur->setScout($scout);
                        $utilisateur->setTelephone($scout->getTelephone());
                        $this->entityManager->persist($utilisateur);
                        $this->entityManager->flush();

                        $this->logger->info('✅ Utilisateur créé', [
                            'scout_id' => $scout->getId(),
                            'phone' => $scout->getTelephone()
                        ]);
                    }

                    // 📱 RÉCUPÉRER LES INFOS DEVICE DEPUIS LE FRONTEND
                    $deviceId = $request->request->get('device_id');
                    $devicePlatform = $request->request->get('device_platform') ?? 'unknown';
                    $deviceModel = $request->request->get('device_model') ?? 'unknown';

                    $this->logger->info('📱 Device info reçues', [
                        'device_id' => $deviceId,
                        'platform' => $devicePlatform,
                        'model' => $deviceModel
                    ]);

                    // 🔍 VÉRIFIER LE DEVICE
                    $deviceCheck = $this->deviceManager->handleDeviceAuthentication(
                        $utilisateur,
                        $deviceId,
                        $devicePlatform,
                        $deviceModel
                    );

                    $this->logger->info('🔍 Résultat device check', [
                        'status' => $deviceCheck['status'],
                        'requires_otp' => $deviceCheck['requires_otp'] ?? false
                    ]);

                    // 📊 PRÉPARER LES DONNÉES DU PROFIL
                    $fonctions = $this->fonctionRepository->findAllByScout($scout->getId());
                    $profilDTO = ProfilDTO::fromScout($fonctions);
                    $champs = $this->champActiviteRepository->findAll();

                    // 🎯 RÉPONSE SELON LE STATUT DU DEVICE
                    $response = [
                        'profil' => $profilDTO->profil,
                        'profil_fonction' => $profilDTO->profil_fonction,
                        'profil_instance' => $profilDTO->profil_instance,
                        'champs_activite' => ChampsDTO::listChamps($champs),
                        'device_check' => $deviceCheck // ✅ CRUCIAL: Infos device
                    ];

                    // Selon le statut du device, ajuster la réponse
                    if ($deviceCheck['status'] === 'ok') {
                        // ✅ DEVICE VÉRIFIÉ → Connexion directe
                        $response['status'] = 'ok';
                        $response['message'] = 'Connexion autorisée';
                    } else {
                        // 📱 VÉRIFICATION REQUISE → Frontend doit envoyer SMS
                        $response['status'] = $deviceCheck['status'];
                        $response['message'] = $deviceCheck['message'];
                        $response['requires_otp'] = true;
                        $response['phone'] = $deviceCheck['phone'];
                        $response['otp_expiry'] = $deviceCheck['otp_expiry'];
                    }

                    return $this->json($response);

                } catch (\Throwable $e) {
                    $this->logger->error('❌ Erreur traitement connexion', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    return $this->json([
                        'error' => true,
                        'message' => $e->getMessage()
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // 📄 CAS FALLBACK (non AJAX)
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

        if (!$getScouts) {
            return $this->redirectToRoute('app_search_phone');
        }

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
}
