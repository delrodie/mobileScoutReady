<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fonctionnalites')]
class PlusController extends AbstractController
{
    #[Route('/', name: 'app_fonctionnalite_list')]
    public function list(): Response
    {
        return $this->render('plus/list.html.twig');
    }
}
