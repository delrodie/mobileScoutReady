<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\ChampsDTO;
use App\DTO\ProfilDTO;
use App\Repository\ChampActiviteRepository;
use App\Repository\FonctionRepository;
use App\Repository\UtilisateurRepository;
use App\Services\DeviceManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/pincode')]
class PinController extends AbstractController
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly DeviceManagerService $deviceManager,
        private readonly FonctionRepository $fonctionRepository,
        private readonly ChampActiviteRepository $champActiviteRepository
    )
    {
    }


    /**
     * ✅ NOUVEAU: Créer le code PIN
     */
    #[Route('/', name: 'app_create_pin', methods: ['POST'])]
    public function createPin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phone = $data['phone'] ?? null;
        $pin = $data['pin'] ?? null;

        if (!$phone || !$pin) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phone]);

        if (!$utilisateur) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $result = $this->deviceManager->createPin($utilisateur, $pin);

        if ($result['success']) {
            // Retourner les données complètes pour connexion
            $scout = $utilisateur->getScout();
            $fonctions = $this->fonctionRepository->findAllByScout($scout->getId());
            $profilDTO = ProfilDTO::fromScout($fonctions);
            $champs = $this->champActiviteRepository->findAll();

            return $this->json([
                'status' => 'success',
                'message' => $result['message'],
                'user_data' => [
                    'profil' => $profilDTO->profil,
                    'profil_fonction' => $profilDTO->profil_fonction,
                    'profil_instance' => $profilDTO->profil_instance,
                    'champs_activite' => ChampsDTO::listChamps($champs)
                ]
            ]);
        }

        return $this->json($result, 400);
    }

    /**
     * ✅ NOUVEAU: Vérifier le code PIN
     */
    #[Route('/verify-pin', name: 'app_verify_pin', methods: ['POST'])]
    public function verifyPin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phone = $data['phone'] ?? null;
        $pin = $data['pin'] ?? null;
        $deviceId = $data['device_id'] ?? null;

        if (!$phone || !$pin || !$deviceId) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $utilisateur = $this->utilisateurRepository->findOneBy(['telephone' => $phone]);

        if (!$utilisateur) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $result = $this->deviceManager->verifyPin($utilisateur, $pin, $deviceId);

        if ($result['success']) {
            // Retourner données complètes
            $scout = $utilisateur->getScout();
            $fonctions = $this->fonctionRepository->findAllByScout($scout->getId());
            $profilDTO = ProfilDTO::fromScout($fonctions);
            $champs = $this->champActiviteRepository->findAll();

            return $this->json([
                'status' => 'success',
                'message' => $result['message'],
                'user_data' => [
                    'profil' => $profilDTO->profil,
                    'profil_fonction' => $profilDTO->profil_fonction,
                    'profil_instance' => $profilDTO->profil_instance,
                    'champs_activite' => ChampsDTO::listChamps($champs)
                ]
            ]);
        }

        return $this->json($result, 401);
    }
}

