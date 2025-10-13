<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inscription')]
class InscriptionController extends AbstractController
{
    #[Route('/', name:'app_inscription_civile')]
    public function civile(Request $request): Response
    {
        return $this->render('scout/inscription_civile.html.twig',[
            'phone' => $request->getSession()->get('_phone_input') ?? null,
        ]);
    }

    #[Route('/scoute', name:'app_inscription_scoute')]
    public function scoute(Request $request): Response
    {

    }
}
