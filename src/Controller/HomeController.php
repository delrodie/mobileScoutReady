<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ScoutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_search_phone');
//        return $this->render('default/home.html.twig');
    }

    #[Route('/phone', name:'app_search_phone', methods: ['GET','POST'])]
    public function phone(Request $request, ScoutRepository $scoutRepository)
    {

        if ($this->isCsrfTokenValid('_searchPhone', $request->get('_csrf_token'))){
            $phoneRequest = $request->request->get('_phone_search');
            $scout = $scoutRepository->findOneBy(['telephone' => $phoneRequest]);

            if (!$scout){
                return $this->redirectToRoute('app_inscription_civile');
            }

            return $this->redirectToRoute('app_home');
        }
        return $this->render('default/_search_phone.html.twig');
    }
}
