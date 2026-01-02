<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profil')]
class ProfilController extends AbstractController
{
    #[Route('/', name:'app_profil_index')]
    public function index(): Response
    {
        return $this->render('profil/index.html.twig');
    }

    #[Route('/civile', name: 'app_profil_civil')]
    public function civil(): Response
    {
        return $this->render('profil/civil.html.twig');
    }

    #[Route('/infos/scoute', name: 'app_profil_infos_scoute')]
    public function infosScoute()
    {
        return $this->render('profil/infos_scoute.html.twig');
    }

    #[Route('/infos/scoute/qr-code', name: 'app_profil_infos_scoute_qrcode')]
    public function qrCode()
    {
        return $this->render('profil/qrcode.html.twig');
    }

    #[ROute('/infos/scoute/complementaires/adulte', name: 'app_profil_infos_complementaires_adulte')]
    public function infosComplementaires(): Response
    {
        return $this->render('profil/infos_complementaires.html.twig');
    }
}
