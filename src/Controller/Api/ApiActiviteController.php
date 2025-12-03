<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Enum\InstanceType;
use App\Mapper\ActiviteMapper;
use App\Repository\ActiviteRepository;
use App\Repository\AutorisationPointageActiviteRepository;
use App\Repository\InstanceRepository;
use App\Repository\ScoutRepository;
use App\Services\GestionInstance;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activite')]
class ApiActiviteController extends AbstractController
{
    public function __construct(private readonly ScoutRepository $scoutRepository, private readonly InstanceRepository $instanceRepository, private readonly ActiviteRepository $activiteRepository, private readonly ActiviteMapper $activiteMapper, private readonly AutorisationPointageActiviteRepository $autorisationPointageActiviteRepository, private readonly GestionInstance $gestionInstance)
    {
    }

    #[Route('/', name: 'api_activite_list', methods: ['POST'])]
    public function list(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $slug = $requestData['slug'] ?? null;
        $code = $requestData['code'] ?? null;
        $instanceId = $requestData['instance'] ?? null;
        $instance_parent = $requestData['parentId'] ?? null;

        if (!$slug || !$code || !$instanceId) {
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

        // Gestion instance
        $instance = $this->instanceRepository->findOneBy(['id' => (int) $instanceId]);
        if (!$instance){
            return $this->json([
                'error' => "Instance introuvable"
            ], Response::HTTP_NOT_FOUND);
        }

        // Activités de groupes
        $activites = $this->getActivitesByGroupe($instance);

        $data = array_map(fn($a) => $this->activiteMapper->toDto($a), $activites);
        //dump($data);
        return $this->json([
            'data' => $data
        ]);


    }

    protected function getActivitesByGroupe($instance)
    {
        // 1. On récupère tous les IDs utiles
        $ids = $this->gestionInstance->resolveInstanceIds($instance);

        // 2. Une seule requête pour tout récupérer
        $activites = $this->activiteRepository->findActivitesAvenirForInstances($ids);

        // 3. Plus besoin de fusionner ou dé-doublonner si la BDD est propre
        return $activites;
    }

}
