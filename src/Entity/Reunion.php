<?php

namespace App\Entity;

use App\Repository\ReunionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReunionRepository::class)]
class Reunion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $attente = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateAt = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureFin = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $cible = null;

    #[ORM\ManyToOne]
    private ?ChampActivite $champs = null;

    #[ORM\ManyToOne]
    private ?Instance $instance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $branche = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $urlPointage = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $createdBy = null;

    /**
     * @var Collection<int, Assister>
     */
    #[ORM\OneToMany(targetEntity: Assister::class, mappedBy: 'reunion')]
    private Collection $participants;

    /**
     * @var Collection<int, AutorisationPointageReunion>
     */
    #[ORM\OneToMany(targetEntity: AutorisationPointageReunion::class, mappedBy: 'reunion')]
    private Collection $autorisations;

    #[ORM\Column(length: 18, nullable: true)]
    private ?string $code = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->autorisations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getObjectif(): ?string
    {
        return $this->objectif;
    }

    public function setObjectif(?string $objectif): static
    {
        $this->objectif = $objectif;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAttente(): ?string
    {
        return $this->attente;
    }

    public function setAttente(?string $attente): static
    {
        $this->attente = $attente;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getDateAt(): ?\DateTime
    {
        return $this->dateAt;
    }

    public function setDateAt(?\DateTime $dateAt): static
    {
        $this->dateAt = $dateAt;

        return $this;
    }

    public function getHeureDebut(): ?\DateTime
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTime $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTime
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTime $heureFin): static
    {
        $this->heureFin = $heureFin;

        return $this;
    }

    public function getCible(): ?array
    {
        return $this->cible;
    }

    public function setCible(?array $cible): static
    {
        $this->cible = $cible;

        return $this;
    }

    public function getChamps(): ?ChampActivite
    {
        return $this->champs;
    }

    public function setChamps(?ChampActivite $champs): static
    {
        $this->champs = $champs;

        return $this;
    }

    public function getInstance(): ?Instance
    {
        return $this->instance;
    }

    public function setInstance(?Instance $instance): static
    {
        $this->instance = $instance;

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

    public function getUrlPointage(): ?string
    {
        return $this->urlPointage;
    }

    public function setUrlPointage(?string $urlPointage): static
    {
        $this->urlPointage = $urlPointage;

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

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, Assister>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Assister $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setReunion($this);
        }

        return $this;
    }

    public function removeParticipant(Assister $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getReunion() === $this) {
                $participant->setReunion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AutorisationPointageReunion>
     */
    public function getAutorisations(): Collection
    {
        return $this->autorisations;
    }

    public function addAutorisation(AutorisationPointageReunion $autorisation): static
    {
        if (!$this->autorisations->contains($autorisation)) {
            $this->autorisations->add($autorisation);
            $autorisation->setReunion($this);
        }

        return $this;
    }

    public function removeAutorisation(AutorisationPointageReunion $autorisation): static
    {
        if ($this->autorisations->removeElement($autorisation)) {
            // set the owning side to null (unless already changed)
            if ($autorisation->getReunion() === $this) {
                $autorisation->setReunion(null);
            }
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }
}
