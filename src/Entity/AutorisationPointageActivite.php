<?php

namespace App\Entity;

use App\Repository\AutorisationPointageActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutorisationPointageActiviteRepository::class)]
class AutorisationPointageActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\ManyToOne]
    private ?Scout $scout = null;


    #[ORM\ManyToOne(inversedBy: 'autorisations')]
    private ?Activite $activite;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    private ?ArrayCollection $pointeurs = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getScout(): ?Scout
    {
        return $this->scout;
    }

    public function setScout(?Scout $scout): static
    {
        $this->scout = $scout;
        return $this;
    }

    public function getActivite(): ?Activite
    {
        return $this->activite;
    }

    public function setActivite(?Activite $activite): static
    {
        $this->activite = $activite;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPointeurs(): ?ArrayCollection
    {
        return $this->pointeurs;
    }

    public function setPointeurs(?ArrayCollection $pointeurs): static
    {
        $this->pointeurs = $pointeurs;
        return $this;
    }
}
