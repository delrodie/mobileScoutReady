<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FonctionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/communaute')]
class CommunauteController extends AbstractController
{
    public function __construct(
        private FonctionRepository $fonctionRepository
    )
    {
    }

    #[Route('/', name: 'app_communaute_list')]
    public function list(): Response
    {
//        dd($this->fonctionRepository->findOneByScout(3));
        return $this->render('communaute/list.html.twig');
    }
}
