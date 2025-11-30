<?php

namespace App\Entity;

use App\Repository\ParticiperRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticiperRepository::class)]
class Participer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Activite $activite = null;

    #[ORM\ManyToOne]
    private ?Scout $scout = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $pointageAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observation = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getScout(): ?Scout
    {
        return $this->scout;
    }

    public function setScout(?Scout $scout): static
    {
        $this->scout = $scout;

        return $this;
    }

    public function getPointageAt(): ?\DateTimeImmutable
    {
        return $this->pointageAt;
    }

    public function setPointageAt(?\DateTimeImmutable $pointageAt): static
    {
        $this->pointageAt = $pointageAt;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;

        return $this;
    }
}
