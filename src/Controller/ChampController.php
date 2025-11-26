<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ChampActivite;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/champs')]
class ChampController extends AbstractController
{
    #[Route('/{id}', name: 'app_champ_show', methods: ['GET'])]
    public function show(ChampActivite $champ): Response
    {
        return $this->render('default/champs_activite.html.twig',[
            'champ' => $champ
        ]);
    }
}
