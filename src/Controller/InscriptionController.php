<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inscription')]
class InscriptionController extends AbstractController
{
    #[Route('/', name:'app_inscription_civile')]
    public function civile(): Response
    {
        return $this->render('default/inscription_cvile.html.twig');
    }
}
