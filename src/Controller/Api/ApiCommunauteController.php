<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Enum\FonctionPoste;
use App\Enum\ScoutStatut;
use App\Repository\FonctionRepository;
use App\Repository\InstanceRepository;
use App\Repository\ScoutRepository;
use App\Services\UtilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/communaute')]
class ApiCommunauteController extends AbstractController
{
    public function __construct(
        private ScoutRepository    $scoutRepository,
        private InstanceRepository $instanceRepository,
        private FonctionRepository $fonctionRepository, private readonly UtilityService $utilityService, private readonly RequestStack $requestStack,
    )
    {
    }

    #[Route('/', name:'api_communaute_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $donnees = json_decode($request->getContent(), true); //dump($donnees);
        $slug = $donnees['slug'] ?? null;
        $code = $donnees['code'];
        $instance = $donnees['instance'];
        $instance_parent = $donnees['parentId'];

        if (!$slug){
            return $this->json(['error' => 'Paramètres manquants'], Response::HTTP_BAD_REQUEST);
        }

        $scoutConnecte = $this->scoutRepository->findOneBy(['slug' => $slug]); //dump($scoutConnecte);
        if (!$scoutConnecte){
            return $this->json(['error' => 'Profil introuvable'], Response::HTTP_NOT_FOUND);
        }

        dump($scoutConnecte);

        $statut = $scoutConnecte->getStatut();
        $fonction = $this->fonctionRepository->findOneByScout($scoutConnecte->getId());

        if ($statut === ScoutStatut::JEUNE){ //dump($fonction->getInstance()->getInstanceParent()->getId());
            $fonctions = $this->fonctionRepository->findCommunauteByBranche(
                $scoutConnecte->getId(),
                $fonction->getInstance()->getInstanceParent()->getId(),
                $fonction->getBranche(),
                $this->utilityService->annee()
            );
            $resultats = $this->getResultat($fonctions);

        }else{
            $poste = $fonction?->getPoste();
            if ($poste === FonctionPoste::REGIONAL ){
                $districts = $this->instanceRepository->findBy(['instanceParent' => $fonction->getInstance()->getId()]);

                $fonctions=[];
                foreach ($districts as $district){
                    $fonctions[] = $this->fonctionRepository->findCommunauteByDistrict(
                        $scoutConnecte->getId(),
                        $district->getId(),
                        $this->utilityService->annee()
                    );
                }
            }elseif($poste === FonctionPoste::DISTRICT){
                $fonctions[] = $this->fonctionRepository->findCommunauteByDistrict(
                    $scoutConnecte->getId(),
                    $fonction->getInstance()->getId(),
                    $this->utilityService->annee()
                );
            }else{
                $fonctions[] = $this->fonctionRepository->findCommunauteByGroupe(
                    $scoutConnecte->getId(),
                    $fonction->getInstance()->getId(),
                    $this->utilityService->annee()
                );

                //dump($fonctions);
            }

            // Les resultats
            $resultats = array_merge(
                ...array_map(fn ($f) => $this->getResultat($f), $fonctions)
            );


        }

        dump($resultats);

        return $this->json($resultats);
    }

    public function getAvatarFile($dateNaiss, $genre): string
    {
        $type = $this->utilityService->avatar($dateNaiss, $genre);
        return match ($type){
            'Garçon' => '/avatar/avatar_garcon.png',
            'Fille' => '/avatar/avatar_fille.png',
            'Homme' => '/avatar/avatar_homme.png',
            'Femme' => '/avatar/avatar_femme.png',
            default => '/avatar/garcon.png',
        };
    }

    protected function getResultat($fonctions): array
    {
        $resultats = []; //dump($fonctions);
        foreach ($fonctions as $fonction){
            if(!$fonction){
                continue;
            }

            if ($fonction->getScout()->getStatut()->value === 'JEUNE'){
                $titre = $fonction->getBranche() ? $fonction->getbranche() : null;
            }else{
                $poste = $fonction->getPoste() ? FonctionPoste::from($fonction->getPoste())->label()  : null;
                $details = $fonction->getDetailPoste() ? $fonction->getDetailPoste() : null;
                $titre = "{$poste} - {$details}"; //dump("Titre: {$titre}");
            }

            $resultats [] = [
                'id' => $fonction->getScout()->getId(),
                'slug' => $fonction->getScout()->getSlug(),
                'nom' => $fonction->getScout()->getNom(),
                'prenom' => $fonction->getScout()->getPrenom(),
                'statut' => $fonction->getScout()->getStatut()->value,
                'fonction' => $titre,
                'instance' => $fonction->getInstance()->getNom(),
                'avatar' => $this->getAvatarFile($fonction->getScout()->getDateNaissance(), $fonction->getScout()->getSexe()),
                'validation' => $fonction->isValidation(),
                'url' => $this->generateUrl('app_communaute_membre',['slug' => $fonction->getScout()->getSlug()]),
            ];
        }

        return $resultats;
    }
}
