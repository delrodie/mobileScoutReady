<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('default/home.html.twig');
    }

    #[Route('/apropos', name:'app_home_propos')]
    public function propos()
    {
        return $this->render('default/propos.html.twig');
    }
}
