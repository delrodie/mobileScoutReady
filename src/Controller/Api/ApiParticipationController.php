<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Mapper\ActiviteMapper;
use App\Repository\ActiviteRepository;
use App\Repository\AssisterRepository;
use App\Repository\ParticiperRepository;
use App\Repository\ReunionRepository;
use App\Repository\ScoutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/participation')]
class ApiParticipationController extends AbstractController
{
    public function __construct(private readonly ScoutRepository $scoutRepository, private readonly ActiviteMapper $activiteMapper, private readonly ActiviteRepository $activiteRepository, private readonly ParticiperRepository $participerRepository, private readonly ReunionRepository $reunionRepository, private readonly AssisterRepository $assisterRepository)
    {
    }

    #[Route('/', name:'api_participation_activite', methods: ['POST'])]
    public function activite(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $slug = $requestData['slug'] ?? null;
        $code = $requestData['code'] ?? null;

        if (!$slug || !$code) {
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

        $participations = $this->participerRepository->findActiviteByScout($slug);
        $activites = [];
        foreach ($participations as $participation) {
            $activites[] = $participation->getActivite();
        }

        $data = array_map(fn($a) => $this->activiteMapper->toDto($a), $activites);
        //dump($data);
        return $this->json([
            'data' => $data
        ]);

    }

    #[Route('/reunion', name: 'api_participation_reunion', methods: ['POST'])]
    public function reunion(Request $request)
    {
        $requestData = json_decode($request->getContent(), true);
        $slug = $requestData['slug'] ?? null;
        $code = $requestData['code'] ?? null;

        if (!$slug || !$code) {
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

        $participations = $this->assisterRepository->findReunionByScout($slug);
        $reunions = [];
        foreach ($participations as $participation) {
            $reunions[] = $participation->getActivite();
        }

        $data = array_map(fn($a) => $this->activiteMapper->toDto($a), $reunions);
        //dump($data);
        return $this->json([
            'data' => $data
        ]);
    }
}
