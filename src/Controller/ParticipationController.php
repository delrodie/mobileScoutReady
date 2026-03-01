<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participation')]
class ParticipationController extends AbstractController
{
    #[Route('/', name:'app_participation_activite', methods: ['GET'])]
    public function activite(): Response
    {
        return $this->render('participation/activite.html.twig');
    }

    #[Route('/reunion', name:'app_participation_reunion', methods: ['GET'])]
    public function reunion()
    {
        return $this->render('participation/reunion.html.twig');
    }
}
