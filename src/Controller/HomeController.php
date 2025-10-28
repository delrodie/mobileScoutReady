<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ScoutRepository;
use App\Services\UtilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(private readonly UtilityService $utilityService)
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_intro_synchro');
//        return $this->render('default/_search_phone.html.twig');
    }

    #[Route('/header', name: 'app_main_header')]
    public function header(): Response
    {

        return $this->render('default/_main_header.html.twig');
    }
}
