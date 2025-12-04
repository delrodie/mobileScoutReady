<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FonctionRepository;
use App\Repository\ScoutRepository;
use App\Services\UtilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/communaute')]
class CommunauteController extends AbstractController
{
    public function __construct(
        private FonctionRepository $fonctionRepository,
        private readonly ScoutRepository $scoutRepository,
        private readonly UtilityService $utilityService
    )
    {
    }

    #[Route('/', name: 'app_communaute_list')]
    public function list(): Response
    {
        return $this->render('communaute/list.html.twig');
    }

    #[Route('/{slug}', name: 'app_communaute_membre', methods: ['GET'])]
    public function membre($slug): Response
    {
        return $this->render('communaute/membre.html.twig',[
            'membre' => $this->fonctionRepository->findOneByScoutSlug($slug)
        ]);
    }
}
