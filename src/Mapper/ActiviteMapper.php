<?php

namespace App\Mapper;

use App\DTO\ActiviteDTO;
use App\DTO\AutorisationDTO;
use App\Entity\Activite;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActiviteMapper
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    )
    {
    }

    public function toDto(Activite $a)
    {
        $dto = new ActiviteDTO();

        $dto->id = $a->getId();
        $dto->titre = $a->getTitre();
        $dto->theme = $a->getTheme();
        $dto->description = $a->getDescription();
        $dto->lieu = $a->getLieu();
        $dto->dateDebut = $a->getDateDebutAt()->format('d/m/Y');
        $dto->dateFin = $a->getDateFinAt()->format('d/m/Y');
        $dto->heureDebut = $a->getHeureDebut()->format('h:i');
        $dto->heureFin = $a->getHeureFin()->format('h:i');
        $dto->cible = $a->getCible();
        $dto->affiche = "/uploads/activites/affiche/".$a->getAffiche();
        $dto->urlPointage = $a->getUrlPointage();
        $dto->urlShow = $this->urlGenerator->generate('app_activite_show', ['id' => $a->getId()]);
        $dto->promotion = $a->isPromotion();

        $dto->instance = [
            'id' => $a->getInstance()->getId(),
            'nom' => $a->getInstance()->getNom(),
            'type' => $a->getInstance()->getType()->value,
        ];

        // Autorisation
        $dto->autorisations = [];
        foreach ($a->getAutorisations() as $autorisation) {
            $autDto = new AutorisationDTO();
            $autDto->id = $autorisation->getId();
            $autDto->role = $autorisation->getRole();

            array_map(function($scout) use ($autDto) {
                 $autDto->scout = [
                    'id' => $scout->getId(),
                    'nom' => $scout->getNom(),
                    'prenom' => $scout->getPrenom(),
                ];
            }, $autorisation->getScout()->toArray());


            $dto->autorisations[] = $autDto;
        }

        return $dto;

    }
}
