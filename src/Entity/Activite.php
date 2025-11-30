<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $theme = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateDebutAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateFinAt = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cible = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $affiche = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tdr = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $urlPointage = null;

    #[ORM\ManyToOne]
    private ?Instance $instance = null;

    /**
     * @var Collection<int, AutorisationPointageActivite>
     */
    #[ORM\ManyToMany(targetEntity: AutorisationPointageActivite::class, mappedBy: 'activite')]
    private Collection $autorisations;

    /**
     * @var Collection<int, Participer>
     */
    #[ORM\OneToMany(targetEntity: Participer::class, mappedBy: 'activite')]
    private Collection $participants;

    public function __construct()
    {
        $this->autorisations = new ArrayCollection();
        $this->participants = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

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

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getDateDebutAt(): ?\DateTime
    {
        return $this->dateDebutAt;
    }

    public function setDateDebutAt(?\DateTime $dateDebutAt): static
    {
        $this->dateDebutAt = $dateDebutAt;

        return $this;
    }

    public function getDateFinAt(): ?\DateTime
    {
        return $this->dateFinAt;
    }

    public function setDateFinAt(?\DateTime $dateFinAt): static
    {
        $this->dateFinAt = $dateFinAt;

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

    public function getCible(): ?string
    {
        return $this->cible;
    }

    public function setCible(?string $cible): static
    {
        $this->cible = $cible;

        return $this;
    }

    public function getAffiche(): ?string
    {
        return $this->affiche;
    }

    public function setAffiche(?string $affiche): static
    {
        $this->affiche = $affiche;

        return $this;
    }

    public function getTdr(): ?string
    {
        return $this->tdr;
    }

    public function setTdr(?string $tdr): static
    {
        $this->tdr = $tdr;

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

    public function getInstance(): ?Instance
    {
        return $this->instance;
    }

    public function setInstance(?Instance $instance): static
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * @return Collection<int, AutorisationPointageActivite>
     */
    public function getAutorisations(): Collection
    {
        return $this->autorisations;
    }

    public function addAutorisation(AutorisationPointageActivite $autorisation): static
    {
        if (!$this->autorisations->contains($autorisation)) {
            $this->autorisations->add($autorisation);
            $autorisation->addActivite($this);
        }

        return $this;
    }

    public function removeAutorisation(AutorisationPointageActivite $autorisation): static
    {
        if ($this->autorisations->removeElement($autorisation)) {
            $autorisation->removeActivite($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Participer>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participer $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setActivite($this);
        }

        return $this;
    }

    public function removeParticipant(Participer $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getActivite() === $this) {
                $participant->setActivite(null);
            }
        }

        return $this;
    }
}
