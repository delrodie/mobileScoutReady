<?php

namespace App\Entity;

use App\Repository\AutorisationPointageActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutorisationPointageActiviteRepository::class)]
class AutorisationPointageActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Scout>
     */
    #[ORM\ManyToMany(targetEntity: Scout::class, inversedBy: 'autorisationPointage')]
    private Collection $scout;

    /**
     * @var Collection<int, Activite>
     */
    #[ORM\ManyToMany(targetEntity: Activite::class, inversedBy: 'autorisations')]
    private Collection $activite;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->scout = new ArrayCollection();
        $this->activite = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Scout>
     */
    public function getScout(): Collection
    {
        return $this->scout;
    }

    public function addScout(Scout $scout): static
    {
        if (!$this->scout->contains($scout)) {
            $this->scout->add($scout);
        }

        return $this;
    }

    public function removeScout(Scout $scout): static
    {
        $this->scout->removeElement($scout);

        return $this;
    }

    /**
     * @return Collection<int, Activite>
     */
    public function getActivite(): Collection
    {
        return $this->activite;
    }

    public function addActivite(Activite $activite): static
    {
        if (!$this->activite->contains($activite)) {
            $this->activite->add($activite);
        }

        return $this;
    }

    public function removeActivite(Activite $activite): static
    {
        $this->activite->removeElement($activite);

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
}
