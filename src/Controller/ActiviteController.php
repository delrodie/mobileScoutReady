<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activites')]
class ActiviteController extends AbstractController
{
    #[Route('/', name:'app_activite_index')]
    public function index(): Response
    {
        return $this->render('activite/index.html.twig');
    }
}
