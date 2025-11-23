<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\ChampsDTO;
use App\DTO\ProfilDTO;
use App\Repository\ChampActiviteRepository;
use App\Repository\FonctionRepository;
use App\Repository\ScoutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/intro')]
class IntroController extends AbstractController
{
    public function __construct(
        private readonly FonctionRepository $fonctionRepository,
        private readonly ChampActiviteRepository $champActiviteRepository
    )
    {
    }

    #[Route('/', name:'app_intro_synchro')]
    public function synchro(): Response
    {
        return $this->render('default/synchro.html.twig');
    }

    #[Route('/phone', name:'app_search_phone', methods: ['GET','POST'])]
    public function phone(Request $request, ScoutRepository $scoutRepository): Response
    {
        $session = $request->getSession();
//        $fonctions = $this->fonctionRepository->findAllByScout(2);
//        dd($fonctions);

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('_searchPhone', $request->get('_csrf_token'))) {

            $phoneRequest = $request->request->get('_phone_search');
            $scouts = $scoutRepository->findBy(['telephone' => $phoneRequest]);

            $session->set('_phone_input', $phoneRequest);

            // Aucun compte trouvé
            if (!$scouts) {
                if ($request->headers->has('Turbo-Frame')) {
                    return $this->render('default/_search_error.html.twig', [
                        'message' => "Numéro introuvable. Veuillez réessayer."
                    ]);
                }

                if ($request->isXmlHttpRequest()){
                    return $this->json(['status' => 'nouveau'], Response::HTTP_OK);
                }
                return $this->redirectToRoute('app_inscription_choixregion');
            }

            // Si c’est un parent → choix du profil
            if ($scouts[0]->isPhoneParent()) {
                $session->set('_getScouts', $scouts);
            }



            // Si requête AJAX (depuis Stimulus)
            if ($request->isXmlHttpRequest()) {
                $scout = $scouts[0];
                $fonctions = $this->fonctionRepository->findAllByScout($scout->getId());
                $profilDTO = ProfilDTO::fromScout($fonctions);

                $champs = $this->champActiviteRepository->findAll();
                //dump(ChampsDTO::listChamps($champs));

                return $this->json([
                    'profil' => $profilDTO->profil,
                    'profil_fonction' => $profilDTO->profil_fonction,
                    'profil_instance' => $profilDTO->profil_instance,
                    'champs_activite' => ChampsDTO::listChamps($champs)
                ]);
            }

            // Cas fallback (classique)
            $session->set('_profil', $scouts[0]);
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('default/_search_phone.html.twig');
    }


    #[Route('/choix/profil', name: 'app_choix_profil', methods: ['GET','POST'])]
    public function choixProfil(Request $request): Response
    {
        $session = $request->getSession();
        $getScouts = $session->get('_getScouts');
        if (!$getScouts) return $this->redirectToRoute('app_search_phone');

        return $this->render('default/_choix_profil.html.twig', [
            'scouts' => $getScouts,
            'phone' => $session->get('_phone_input')
        ]);
    }

    #[Route('/profil/{slug}', name: 'app_profil_selectionne', methods: ['GET'])]
    public function selectProfil(Request $request, ScoutRepository $scoutRepository, string $slug): Response
    {
        $scout = $scoutRepository->findOneBy(['slug' => $slug]);
        if (!$scout){
            if ($request->isXmlHttpRequest()){
                return $this->json([
                    'error' => 'Profil non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            return $this->redirectToRoute('app_search_phone');
        }

        if ($request->isXmlHttpRequest()){
            $fonctions = $this->fonctionRepository->findAllByScout($scout->getId());
            $profilDTO = ProfilDTO::fromScout($fonctions);

            $champs = $this->champActiviteRepository->findAll();

            return $this->json([
                'profil' => $profilDTO->profil,
                'profil_fonction' => $profilDTO->profil_fonction,
                'profil_instance' => $profilDTO->profil_instance,
                'champs_activite' => ChampsDTO::listChamps($champs)
            ]);
        }

        $request->getSession()->set('profil', $scout);
        return $this->redirectToRoute('app_accueil');
    }
}
