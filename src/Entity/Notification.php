<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    public const TYPE_INFO = "info";
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_DANGER = 'danger';

    public const TARGET_ALL = 'all';
    public const TARGET_SPECIFIC = 'specific';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = self::TYPE_INFO;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeCible = self::TARGET_ALL;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $urlAction = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $libelleAction = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icone = null;

    #[ORM\Column(nullable: true)]
    private ?bool $estActif = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expireLe = null;

    /**
     * @var Collection<int, UtilisateurNotification>
     */
    #[ORM\OneToMany(targetEntity: UtilisateurNotification::class, mappedBy: 'notification', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $utilisateurNotifications;

    /**
     * @var Collection<int, Notificationlog>
     */
    #[ORM\OneToMany(targetEntity: Notificationlog::class, mappedBy: 'notification', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $logs;

    public function __construct()
    {
        $this->utilisateurNotifications = new ArrayCollection();
        $this->logs = new ArrayCollection();
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeCible(): ?string
    {
        return $this->typeCible;
    }

    public function setTypeCible(?string $typeCible): static
    {
        $this->typeCible = $typeCible;

        return $this;
    }

    public function getUrlAction(): ?string
    {
        return $this->urlAction;
    }

    public function setUrlAction(?string $urlAction): static
    {
        $this->urlAction = $urlAction;

        return $this;
    }

    public function getLibelleAction(): ?string
    {
        return $this->libelleAction;
    }

    public function setLibelleAction(?string $libelleAction): static
    {
        $this->libelleAction = $libelleAction;

        return $this;
    }

    public function getIcone(): ?string
    {
        return $this->icone;
    }

    public function setIcone(?string $icone): static
    {
        $this->icone = $icone;

        return $this;
    }

    public function isEstActif(): ?bool
    {
        return $this->estActif;
    }

    public function setEstActif(?bool $estActif): static
    {
        $this->estActif = $estActif;

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
    public function setCreateAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getExpireLe(): ?\DateTimeImmutable
    {
        return $this->expireLe;
    }

    public function setExpireLe(?\DateTimeImmutable $expireLe): static
    {
        $this->expireLe = $expireLe;

        return $this;
    }

    /**
     * @return Collection<int, UtilisateurNotification>
     */
    public function getUtilisateurNotifications(): Collection
    {
        return $this->utilisateurNotifications;
    }

    public function addUtilisateurNotification(UtilisateurNotification $utilisateurNotification): static
    {
        if (!$this->utilisateurNotifications->contains($utilisateurNotification)) {
            $this->utilisateurNotifications->add($utilisateurNotification);
            $utilisateurNotification->setNotification($this);
        }

        return $this;
    }

    public function removeUtilisateurNotification(UtilisateurNotification $utilisateurNotification): static
    {
        if ($this->utilisateurNotifications->removeElement($utilisateurNotification)) {
            // set the owning side to null (unless already changed)
            if ($utilisateurNotification->getNotification() === $this) {
                $utilisateurNotification->setNotification(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notificationlog>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Notificationlog $notificationlog): static
    {
        if (!$this->logs->contains($notificationlog)) {
            $this->logs->add($notificationlog);
            $notificationlog->setNotification($this);
        }

        return $this;
    }

    public function removeLog(Notificationlog $notificationlog): static
    {
        if ($this->logs->removeElement($notificationlog)) {
            // set the owning side to null (unless already changed)
            if ($notificationlog->getNotification() === $this) {
                $notificationlog->setNotification(null);
            }
        }

        return $this;
    }

    public function estExpiree(): bool
    {
        return $this->expireLe !== null && $this->expireLe < new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->titre ?? "Notification #" . $this->id;
    }
}
