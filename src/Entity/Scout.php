<?php

namespace App\Entity;

use App\Enum\ScoutStatut;
use App\Repository\ScoutRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: ScoutRepository::class)]
//#[Broadcast]
class Scout
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $slug = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $matricule = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $qrCodeToken = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateNaissance = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $sexe = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCodeFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(enumType: ScoutStatut::class)]
    private ?ScoutStatut $statut = ScoutStatut::JEUNE;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'scout', cascade: ['persist', 'remove'])]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(nullable: true)]
    private ?bool $phoneParent = null;

    public function __construct()
    {
        $this->slug = Uuid::v4();
        $this->qrCodeToken = Uuid::v4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): static
    {
        $this->matricule = $matricule;

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

    public function getQrCodeToken(): ?Uuid
    {
        return $this->qrCodeToken;
    }

    public function setQrCodeToken(?Uuid $qrCodeToken): static
    {
        $this->qrCodeToken = $qrCodeToken;

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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTime $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getQrCodeFile(): ?string
    {
        return $this->qrCodeFile;
    }

    public function setQrCodeFile(?string $qrCodeFile): static
    {
        $this->qrCodeFile = $qrCodeFile;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getStatut(): ?ScoutStatut
    {
        return $this->statut;
    }

    public function setStatut(?ScoutStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        // unset the owning side of the relation if necessary
        if ($utilisateur === null && $this->utilisateur !== null) {
            $this->utilisateur->setScout(null);
        }

        // set the owning side of the relation if necessary
        if ($utilisateur !== null && $utilisateur->getScout() !== $this) {
            $utilisateur->setScout($this);
        }

        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function isPhoneParent(): ?bool
    {
        return $this->phoneParent;
    }

    public function setPhoneParent(?bool $phoneParent): static
    {
        $this->phoneParent = $phoneParent;

        return $this;
    }
}
