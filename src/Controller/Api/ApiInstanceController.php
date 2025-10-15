<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\InstanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/instance')]
class ApiInstanceController extends AbstractController
{
    public function __construct(
        private readonly InstanceRepository $instanceRepository
    )
    {
    }

    #[Route('/groupes', name: 'api_instance_groupe_by_district', methods: ['GET'])]
    public function groupeByDistrict(Request $request): Response
    {
        $districtRequest = (int) $request->query->get('district');
        $groupes = $this->instanceRepository->findBy(['instanceParent' => $districtRequest], ['nom' => "ASC"]);

        $data = [];
        foreach ($groupes as $groupe){
            $data[] = [
                'id' => $groupe->getId(),
                'nom' => $groupe->getNom(),
            ];
        }
        return new JsonResponse($data);
    }
}
