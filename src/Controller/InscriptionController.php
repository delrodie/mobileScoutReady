<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Fonction;
use App\Entity\Scout;
use App\Entity\Utilisateur;
use App\Enum\FonctionPoste;
use App\Enum\InstanceType;
use App\Enum\ScoutStatut;
use App\Repository\InstanceRepository;
use App\Services\UtilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inscription')]
class InscriptionController extends AbstractController
{
    public function __construct(
        private readonly InstanceRepository $instanceRepository,
        private readonly UtilityService $utilityService,
        private readonly EntityManagerInterface $entityManager,
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
            'phone' => $session->get('_phone_input'),
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
                'phoneParent' =>  $request->get('_inscription_phoneparent') === 'OUI',
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
            if (!$session->get('inscription_civile')){
                return $this->redirectToRoute('app_search_phone');
            }

            $scout = new Scout();
            $scout->setnom($civilSession['nom']);
            $scout->setPrenom($civilSession['prenom']);
            $scout->setSexe($civilSession['sexe']);
            $scout->setDateNaissance(new \DateTime($civilSession['dateNaissance']));
            $scout->setTelephone($civilSession['phone']);
            $scout->setEmail($civilSession['email']);
            $scout->setphoneParent((bool) $civilSession['phoneParent']);
            $scout->setStatut($statut);
            $this->entityManager->persist($scout);

            // persistance de la fonction
            $poste = $this->utilityService->validForm($request->get('_fonction'));
            $instance = match ($poste){
                "REGIONAL" => $this->utilityService->validForm($request->get('_incription_region')),
                "DISTRICT" => $this->utilityService->validForm($request->get('_district')),
                default => $this->utilityService->validForm($request->get('_inscription_groupe')),
            };
            $instanceEntity = $this->instanceRepository->findOneBy(['id' => $instance]);

            $fonction = new Fonction();
            $fonction->setScout($scout);
            $fonction->setAnnee($this->utilityService->annee());
            $fonction->setBranche($this->utilityService->validForm($request->get('_inscription_branche')));
            $fonction->setPoste($poste);
            $fonction->setDetailPoste($this->utilityService->validForm($request->get('_inscription_poste')));
            $fonction->setInstance($instanceEntity);
            $fonction->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($fonction);

            // Creation du compte utilisateur
            $utilisateur = new Utilisateur();
            $utilisateur->setScout($scout);
            $utilisateur->setTelephone($civilSession['phone']);
            $this->entityManager->persist($utilisateur);

            $this->entityManager->flush();

            $session->set('_phone_input', '');
            $session->set('inscription_civile', '');
            $session->set('_choix_region', '');

            if ($request->headers->get('Turbo-Frame')) {
                return $this->render('scout/_success.stream.html.twig', [
                    'message' => 'Scout enregistré avec succès !'
                ]);
            }
            $response = new RedirectResponse($this->generateUrl('app_accueil'), 303);

            if ($request->headers->get('Turbo-Frame')) {
                $response->headers->set('Turbo-Location', $this->generateUrl('app_accueil'));
            }

            return $response;


        }

        return $this->render('scout/inscription_scout.html.twig',[
            'civil' => $session->get('_civil_input') ?? null,
            'region' => $regionSession,
            'districts' => $this->instanceRepository->findBy(['instanceParent' => $regionSession]),
            'statut' => $statut,
            'age' => $age,
            'fonctions' => FonctionPoste::cases()
        ]);
    }
}
