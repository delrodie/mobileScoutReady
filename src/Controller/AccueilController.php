<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ChampActiviteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/accueil')]
class AccueilController extends AbstractController
{
    public function __construct(
        private readonly ChampActiviteRepository $champActiviteRepository
    )
    {
    }

    #[Route('/', name: 'app_accueil')]
    public function index(Request $request): Response
    {

        return $this->render('default/home.html.twig');
    }
}
