<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ChampActivite;
use App\Repository\ChampActiviteRepository;
use App\Repository\ReunionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/champs')]
class ChampController extends AbstractController
{
    public function __construct(
        private readonly ChampActiviteRepository $champActiviteRepository,
        private readonly ReunionRepository $reunionRepository
    )
    {
    }

    #[Route('/{id}', name: 'app_champ_show', methods: ['GET'])]
    public function show(ChampActivite $champ): Response
    {
        $reunions = $this->reunionRepository->findReunionByChamps($champ->getId());
        $totalMinutes = 0;
        $redacteursUniques = [];

        foreach ($reunions as $reunion) {
            // 1. Calcul de la durée
            $debut = $reunion->getHeureDebut();
            $fin = $reunion->getHeureFin();
            if ($debut && $fin) {
                $interval = $debut->diff($fin);
                $totalMinutes += ($interval->h * 60) + $interval->i;
            }

            // 2. Identification des rédacteurs uniques
            $auteur = $reunion->getCreatedBy(); // Récupère le champ createdBy
            if ($auteur) {
                // On utilise le nom comme clé du tableau pour garantir l'unicité
                $redacteursUniques[$auteur] = true;
            }
        }


        return $this->render('default/champs_activite.html.twig',[
            'champ' => $champ,
            'reunions' => $reunions,
            'total_minutes' => $totalMinutes,
            'nb_redacteurs' => count($redacteursUniques),
        ]);
    }

    #[Route('/{id}/reunions', name: 'app_champ_reunions', methods: ['GET'])]
    public function reunions(ChampActivite $champ): Response
    {
        return $this->render('reunion/modules.html.twig', [
            'champ' => $champ,
        ]);
    }
}
