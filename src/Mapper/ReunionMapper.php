<?php

namespace App\Mapper;

use App\DTO\ReunionDTO;
use App\Entity\Reunion;
use App\Repository\FonctionRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReunionMapper
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator, private readonly FonctionRepository $fonctionRepository,
    )
    {
    }

    public function toDto(Reunion $r)
    {
        $dto = new ReunionDTO();

        $dto->id = $r->getId();
        $dto->titre = $r->getTitre();
        $dto->objectif = $r->getObjectif();
        $dto->description = $r->getDescription();
        $dto->attente = $r->getAttente();
        $dto->lieu = $r->getLieu();
        $dto->dateAt = $r->getDateAt()->format('d/m/Y');
        $dto->heureDebut = $r->getHeureDebut()->format('H:i');
        $dto->heureFin = $r->getHeureFin()->format('H:i');
        $dto->branche = $r->getBranche();
        $dto->urlPointage = $this->urlGenerator->generate('app_pointage_activite');
        $dto->createdAt = $r->getCreatedAt()->format('Y-m-d H:i');
        $dto->createdBy = $r->getCreatedBy();
        $dto->cible = $r->getCible();
        $dto->urlShow = $this->urlGenerator->generate('app_reunion_show', ['id' => $r->getId()]);

        $dto->champs = [
            'id' => $r->getChamps()?->getId(),
            'titre' => $r->getChamps()?->getTitre(),
            'media' => '/uploads/champs/'.$r->getChamps()?->getMedia(),
            'description' => $r->getChamps()?->getDescription(),
        ];

        $dto->instance = [
            'id' => $r->getInstance()?->getId(),
            'nom' => $r->getInstance()?->getNom(),
            'type' => $r->getInstance()?->getType()->value,
        ];

        $fonction = $this->fonctionRepository->findOneByScoutCode($r?->getCreatedBy());
        $dto->auteur = [
            'id' => $fonction?->getScout()->getId(),
            'nom' => $fonction?->getScout()->getNom(),
            'prenom' => $fonction?->getScout()->getPrenom(),
            'poste' => $fonction?->getDetailPoste()
        ];


        return $dto;
    }
}
