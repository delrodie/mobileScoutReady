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
//        return $this->redirectToRoute('app_search_phone');
        return $this->render('default/_search_phone.html.twig');
    }

    #[Route('/phone', name:'app_search_phone', methods: ['GET','POST'])]
    public function phone(Request $request, ScoutRepository $scoutRepository)
    {
        $session = $request->getSession();

        if ($this->isCsrfTokenValid('_searchPhone', $request->get('_csrf_token'))){
            $phoneRequest = $request->request->get('_phone_search');
            $scout = $scoutRepository->findOneBy(['telephone' => $phoneRequest]);

            // Mise en session du numero de telephone
            $session->set('_phone_input', $phoneRequest);

            if (!$scout){
                if ($request->headers->has('Turbo-Frame')){
                    return $this->render('default/_search_error.html.twig', [
                        'message' => "Numéro introuvable. Veuillez réessayer."
                    ]);
                }
                return $this->redirectToRoute('app_inscription_choixregion');
            }

            return $this->redirectToRoute('app_accueil');
        }
        return $this->render('default/_search_phone.html.twig');
    }
}
