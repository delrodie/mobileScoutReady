<?php

namespace App\Entity;

use App\Enum\InstanceType;
use App\Repository\InstanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: InstanceRepository::class)]
class Instance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(enumType: InstanceType::class)]
    private ?InstanceType $type = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'instanceEnfants')]
    private ?self $instanceParent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'instanceParent')]
    private Collection $instanceEnfants;

    #[ORM\Column(length: 72, nullable: true)]
    private ?string $sigle = null;

    public function __construct()
    {
        $this->instanceEnfants = new ArrayCollection();
        $this->slug = Uuid::v4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?Uuid
    {
        return $this->slug;
    }

    public function setSlug(?Uuid $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getType(): ?InstanceType
    {
        return $this->type;
    }

    public function setType(?InstanceType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getInstanceParent(): ?self
    {
        return $this->instanceParent;
    }

    public function setInstanceParent(?self $instanceParent): static
    {
        $this->instanceParent = $instanceParent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getInstanceEnfants(): Collection
    {
        return $this->instanceEnfants;
    }

    public function addInstanceEnfant(self $instanceEnfant): static
    {
        if (!$this->instanceEnfants->contains($instanceEnfant)) {
            $this->instanceEnfants->add($instanceEnfant);
            $instanceEnfant->setInstanceParent($this);
        }

        return $this;
    }

    public function removeInstanceEnfant(self $instanceEnfant): static
    {
        if ($this->instanceEnfants->removeElement($instanceEnfant)) {
            // set the owning side to null (unless already changed)
            if ($instanceEnfant->getInstanceParent() === $this) {
                $instanceEnfant->setInstanceParent(null);
            }
        }

        return $this;
    }

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(?string $sigle): static
    {
        $this->sigle = $sigle;

        return $this;
    }

    public function __toString(): string
    {
        // Sécurise les accès à l'instance parente
        $parentNom = $this->instanceParent?->getNom() ?? '';
        $parentSigle = $this->instanceParent?->getSigle() ?? '';

        // Cas : type NATION → afficher sigle ou nom
        if ($this->type === InstanceType::NATION) {
            return $this->sigle ?? $this->nom ?? '—';
        }

        // Cas : type REGION → afficher (sigle parent) - nom
        if ($this->type === InstanceType::REGION) {
            $prefix = $parentSigle ?: $parentNom;
            return trim(sprintf('%s - %s', $prefix, $this->nom ?? '—'));
        }

        // Cas : type DISTRICT → afficher (nom parent) - nom
        if ($this->type === InstanceType::DISTRICT) {
            return trim(sprintf('%s - %s', $parentNom, $this->nom ?? '—'));
        }

        // Cas par défaut
        $prefix = $parentNom ?: '—';
        return trim(sprintf('%s - %s', $prefix, $this->nom ?? '—'));
    }



}
