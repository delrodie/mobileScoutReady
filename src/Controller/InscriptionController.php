<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\InstanceType;
use App\Enum\ScoutStatut;
use App\Repository\InstanceRepository;
use App\Services\UtilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inscription')]
class InscriptionController extends AbstractController
{
    public function __construct(
        private readonly InstanceRepository $instanceRepository,
        private readonly UtilityService $utilityService
    )
    {
    }

    #[Route('/', name: 'app_inscription_choixregion', methods: ['GET','POST'])]
    public function choixRegion(Request $request): Response
    {
        $session = $request->getSession();

        if ($request->isMethod('POST') &&
            $this->isCsrfTokenValid('__choixRegion', $request->get('_csrf_token'))
        ){
            $regionRequest = (int) $request->request->get('_choix_region');
            $region = $this->instanceRepository->findOneBy(['id' => $regionRequest]);

            if($region){
                $session->set('_choix_region', $region);

                return $this->redirectToRoute('app_inscription_civile');
            }
        }
        return $this->render('scout/choix-region.html.twig',[
            'regions' => $this->instanceRepository->findBy(['type' => InstanceType::REGION], ['nom' => "ASC"]),
        ]);
    }
    #[Route('/civile', name:'app_inscription_civile')]
    public function civile(Request $request): Response
    {
        // S'il n'y a pas de choix de region alors renvoyer vers app_inscription_choixregion
        $session = $request->getSession();

        if (!$session->has('_choix_region')) {
            return $this->redirectToRoute('app_inscription_choixregion');
        }

        if ($request->isMethod('POST') &&
            $this->isCsrfTokenValid('_civil_token', $request->get('_csrf_token'))
        ){
            // Sauvegarde en session des informations
            $session->set('inscription_civile',[
                'nom' => $this->utilityService->validForm($request->get('_inscription_nom')),
                'prenom' => $this->utilityService->validForm($request->get('_inscription_prenom')),
                'sexe' => $this->utilityService->validForm($request->get('_inscription_sexe')),
                'dateNaissance' => $request->get('_inscription_datenaissance'),
                'phone' => $request->get('_inscription_phone'),
                'phoneParent' => $request->get('_inscription_phoneparent'),
                'email' => $this->utilityService->validForm($request->get('_inscription_email')),
            ]);

            return $this->redirectToRoute('app_inscription_scoute');
        }
        return $this->render('scout/inscription_civile.html.twig',[
            'phone' => $session->get('_phone_input') ?? null,
        ]);
    }

    #[Route('/scoute', name:'app_inscription_scoute', methods: ['GET','POST'])]
    public function scoute(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        $regionSession = $session->get('_choix_region');
        $civilSession = $session->get('inscription_civile');

        $age = $this->utilityService->calculAge($civilSession['dateNaissance']);
        if ($age <= 21) $statut = ScoutStatut::JEUNE;
        else $statut = ScoutStatut::ADULTE;

        if ($request->isMethod('POST') &&
            $this->isCsrfTokenValid('_scout_token', $request->get('_csrf_token'))
        ){
            // Sauvegarde du scout dans la base de données
            // Génération du code, qrCodeToken ainsi que le qrCodeMedia
            // Persistence
        }

        return $this->render('scout/inscription_scout.html.twig',[
            'civil' => $session->get('_civil_input') ?? null,
            'region' => $regionSession,
            'districts' => $this->instanceRepository->findBy(['instanceParent' => $regionSession]),
            'statut' => $statut,
            'age' => $age
        ]);
    }
}
