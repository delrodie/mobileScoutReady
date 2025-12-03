<?php

namespace App\Entity;

use App\Repository\AutorisationPointageReunionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutorisationPointageReunionRepository::class)]
class AutorisationPointageReunion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $role = null;

    #[ORM\ManyToOne]
    private ?Scout $scout = null;

    #[ORM\ManyToOne(inversedBy: 'autorisations')]
    private ?Reunion $reunion = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    private ?ArrayCollection $pointeurs = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getScout(): ?Scout
    {
        return $this->scout;
    }

    public function setScout(?Scout $scout): static
    {
        $this->scout = $scout;

        return $this;
    }

    public function getReunion(): ?Reunion
    {
        return $this->reunion;
    }

    public function setReunion(?Reunion $reunion): static
    {
        $this->reunion = $reunion;

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
