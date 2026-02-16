<?php

namespace App\Entity;

use App\Repository\UtilisateurNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurNotificationRepository::class)]
#[ORM\Index(name: 'idx_utilisateur_lue', columns: ['utilisateur_id', 'est_lue'])]
#[ORM\Index(name: 'idx_utilisateur_cree', columns: ['utilisateur_id', 'created_at'])]
#[ORM\HasLifecycleCallbacks]
class UtilisateurNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'utilisateurNotifications')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Notification $notification = null;

    #[ORM\Column(nullable: true)]
    private ?bool $estLue = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $luLe = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getNotification(): ?Notification
    {
        return $this->notification;
    }

    public function setNotification(?Notification $notification): static
    {
        $this->notification = $notification;

        return $this;
    }

    public function isEstLue(): ?bool
    {
        return $this->estLue;
    }

    public function setEstLue(?bool $estLue): static
    {
        $this->estLue = $estLue;

        return $this;
    }

    public function getLuLe(): ?\DateTimeImmutable
    {
        return $this->luLe;
    }

    public function setLuLe(?\DateTimeImmutable $luLe): static
    {
        $this->luLe = $luLe;

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

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function marquerCommeLue():void
    {
        $this->estLue =true;
        $this->luLe =  new \DateTimeImmutable();
    }
}
