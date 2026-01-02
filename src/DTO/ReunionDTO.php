<?php

namespace App\DTO;

class ReunionDTO
{
    public int $id;
    public ?string $titre;
    public ?string $objectif;
    public ?string $description;
    public ?string $attente;
    public ?string $lieu;
    public string $dateAt;
    public string $heureDebut;
    public string $heureFin;

    public ?string $branche;
    public ?string $urlPointage;
    public ?string $createdAt;
    public ?string $createdBy;
    public ?string $urlShow;

    public ?array $cible;
    public ?array $champs;
    public ?array $instance;

    public ?array $auteur;

    public ?int $nbParticipant;
}
