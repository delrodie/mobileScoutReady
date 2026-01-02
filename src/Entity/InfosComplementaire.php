<?php

namespace App\Entity;

use App\Repository\InfosComplementaireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InfosComplementaireRepository::class)]
class InfosComplementaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Scout $scout = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $branche = null;

    #[ORM\Column(nullable: true)]
    private ?bool $formation = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $stageBaseNiveau1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneeBaseNiveau1 = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $stageBaseNiveau2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneeBaseNiveau2 = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $stageAvanceNiveau1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneeAvanceNiveau1 = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $stageAvanceNiveau2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneeAvanceNiveau2 = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $stageAvanceNiveau3 = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneeAvanceNiveau3 = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $stageAvanceNiveau4 = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneeAvanceNiveau4 = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getBranche(): ?string
    {
        return $this->branche;
    }

    public function setBranche(?string $branche): static
    {
        $this->branche = $branche;

        return $this;
    }

    public function isFormation(): ?bool
    {
        return $this->formation;
    }

    public function setFormation(?bool $formation): static
    {
        $this->formation = $formation;

        return $this;
    }

    public function getStageBaseNiveau1(): ?string
    {
        return $this->stageBaseNiveau1;
    }

    public function setStageBaseNiveau1(?string $stageBaseNiveau1): static
    {
        $this->stageBaseNiveau1 = $stageBaseNiveau1;

        return $this;
    }

    public function getAnneeBaseNiveau1(): ?int
    {
        return $this->anneeBaseNiveau1;
    }

    public function setAnneeBaseNiveau1(?int $anneeBaseNiveau1): static
    {
        $this->anneeBaseNiveau1 = $anneeBaseNiveau1;

        return $this;
    }

    public function getStageBaseNiveau2(): ?string
    {
        return $this->stageBaseNiveau2;
    }

    public function setStageBaseNiveau2(?string $stageBaseNiveau2): static
    {
        $this->stageBaseNiveau2 = $stageBaseNiveau2;

        return $this;
    }

    public function getAnneeBaseNiveau2(): ?int
    {
        return $this->anneeBaseNiveau2;
    }

    public function setAnneeBaseNiveau2(?int $anneeBaseNiveau2): static
    {
        $this->anneeBaseNiveau2 = $anneeBaseNiveau2;

        return $this;
    }

    public function getStageAvanceNiveau1(): ?string
    {
        return $this->stageAvanceNiveau1;
    }

    public function setStageAvanceNiveau1(?string $stageAvanceNiveau1): static
    {
        $this->stageAvanceNiveau1 = $stageAvanceNiveau1;

        return $this;
    }

    public function getAnneeAvanceNiveau1(): ?int
    {
        return $this->anneeAvanceNiveau1;
    }

    public function setAnneeAvanceNiveau1(?int $anneeAvanceNiveau1): static
    {
        $this->anneeAvanceNiveau1 = $anneeAvanceNiveau1;

        return $this;
    }

    public function getStageAvanceNiveau2(): ?string
    {
        return $this->stageAvanceNiveau2;
    }

    public function setStageAvanceNiveau2(?string $stageAvanceNiveau2): static
    {
        $this->stageAvanceNiveau2 = $stageAvanceNiveau2;

        return $this;
    }

    public function getAnneeAvanceNiveau2(): ?int
    {
        return $this->anneeAvanceNiveau2;
    }

    public function setAnneeAvanceNiveau2(?int $anneeAvanceNiveau2): static
    {
        $this->anneeAvanceNiveau2 = $anneeAvanceNiveau2;

        return $this;
    }

    public function getStageAvanceNiveau3(): ?string
    {
        return $this->stageAvanceNiveau3;
    }

    public function setStageAvanceNiveau3(?string $stageAvanceNiveau3): static
    {
        $this->stageAvanceNiveau3 = $stageAvanceNiveau3;

        return $this;
    }

    public function getAnneeAvanceNiveau3(): ?int
    {
        return $this->anneeAvanceNiveau3;
    }

    public function setAnneeAvanceNiveau3(?int $anneeAvanceNiveau3): static
    {
        $this->anneeAvanceNiveau3 = $anneeAvanceNiveau3;

        return $this;
    }

    public function getStageAvanceNiveau4(): ?string
    {
        return $this->stageAvanceNiveau4;
    }

    public function setStageAvanceNiveau4(?string $stageAvanceNiveau4): static
    {
        $this->stageAvanceNiveau4 = $stageAvanceNiveau4;

        return $this;
    }

    public function getAnneeAvanceNiveau4(): ?int
    {
        return $this->anneeAvanceNiveau4;
    }

    public function setAnneeAvanceNiveau4(?int $anneeAvanceNiveau4): static
    {
        $this->anneeAvanceNiveau4 = $anneeAvanceNiveau4;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
