<?php

namespace App\Entity;

use App\Repository\AssisterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssisterRepository::class)]
class Assister
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observation = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Reunion $reunion = null;

    #[ORM\ManyToOne]
    private ?Scout $scout = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $pointageAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getReunion(): ?Reunion
    {
        return $this->reunion;
    }

    public function setReunion(?Reunion $reunion): static
    {
        $this->reunion = $reunion;

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
}
