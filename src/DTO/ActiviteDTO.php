<?php

namespace App\DTO;

class ActiviteDTO
{
    public int $id;
    public string $titre;
    public ?string $theme;
    public ?string $description;
    public ?string $lieu;
    public string $affiche;

    public string $dateDebut;
    public string $dateFin;
    public string $heureDebut;
    public string $heureFin;
    public array $cible;
    public ?string $urlPointage;
    public ?string $urlShow;
    public ?bool $promotion;

    public array $instance;        // id, nom, type
    public array $autorisations;
}
