<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Reunion;
use App\Mapper\ReunionMapper;
use App\Repository\AssisterRepository;
use App\Repository\AutorisationPointageReunionRepository;
use App\Repository\InstanceRepository;
use App\Repository\ReunionRepository;
use App\Repository\ScoutRepository;
use App\Services\GestionInstance;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reunion')]
class ApiReunionController extends AbstractController
{
    public function __construct(private readonly ScoutRepository $scoutRepository, private readonly InstanceRepository $instanceRepository, private readonly GestionInstance $gestionInstance, private readonly ReunionRepository $reunionRepository, private readonly ReunionMapper $reunionMapper, private readonly AutorisationPointageReunionRepository $autorisationPointageReunionRepository, private readonly AssisterRepository $assisterRepository)
    {
    }

    #[Route('/', name: 'api_reunion_list', methods: ['POST'])]
    public function list(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $slug = $requestData['slug'] ?? null;
        $code = $requestData['code'] ?? null;
        $instanceId = $requestData['instance'] ?? null;

        if (!$slug || !$code || !$instanceId) {
            notyf()->error("Paramètres manquants, veuillez vous déconnecter puis ous réconnecter.");
            return $this->json(['error' => 'Paramètres manquants'], Response::HTTP_BAD_REQUEST);
        }

        $profilConnecte = $this->scoutRepository->findOneBy(['slug' => $slug]);
        if (!$profilConnecte){
            notyf()->warning("Votre profil est introuvable. Veuillez vous deconnecter puis vous reconnecter");
            return $this->json(['error' => "Profil introuvable"], Response::HTTP_NOT_FOUND);
        }

        // Gestion des instances
        $instance = $this->instanceRepository->findOneBy(['id' => (int) $instanceId]);
        if(!$instance){
            notyf()->warning("Votre profil n'est associé a aucune instance. Veuillez vous deconnecter puis vous reconnecter");
            return $this->json(['error' => "Instance introuvable"], Response::HTTP_NOT_FOUND);
        }

        // reunions
        $reunions = $this->getReunionByInstance($instance);

        $data = array_map(fn($r) => $this->reunionMapper->toDto($r), $reunions);
//        dump($data);

        return $this->json(['data' => $data], Response::HTTP_OK);
    }

    #[Route('/autorisation', name: 'api_reunion_verification_autorisation', methods: ['POST'])]
    public function autorisation(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $slug = $requestData['slug'] ?? null;
        $code = $requestData['code'] ?? null;
        $reunionId = $requestData['reunion'] ?? null;
//        $activiteId = 1;

        //dump('********* Autorisation');
        //dump([$slug, $activiteId]);

        if (!$slug || !$code || !$reunionId) {
            return $this->json([
                'error' => 'Paramètre manquants'
            ], Response::HTTP_BAD_REQUEST);
        }

        $profilConnecte = $this->scoutRepository->findOneBy(['slug' => $slug]);
        if(!$profilConnecte){
            return $this->json([
                'error' => "Profil introuvable"
            ], Response::HTTP_NOT_FOUND);
        }

        $autorisation = $this->autorisationPointageReunionRepository->findAutorisation($profilConnecte->getId(), (int)$reunionId);

        //dump($autorisation);
        $data = [
            'access' => false,
        ];
        if ($autorisation){
            $data = [
                'role' => $autorisation->getRole(),
                'access' => true
            ];
        }

        //dump($data);

        return $this->json($data, Response::HTTP_OK);
    }

    #[ROute('/nombre/{id}', name: 'api_reunion_nombre', methods: ['GET','POST'])]
    public function nombre(Reunion $reunion): Response
    {
        $present = $this->assisterRepository->findBy(['reunion' =>$reunion->getId() ]);

        return $this->json([
            'participant' => count($present),
            'statut' => 'success'
        ], Response::HTTP_OK);
    }


    protected function getReunionByInstance(object $instance)
    {
        // Recupération des Ids utiles
        $ids = $this->gestionInstance->resolveInstanceIds($instance);
        return $this->reunionRepository->findReunionAvenirForInstance($ids);
    }
}
