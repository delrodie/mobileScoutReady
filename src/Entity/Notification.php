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

    // Cibles prédéfinies (groupes)
    public const CIBLE_TOUS_JEUNES = 'tous_jeunes';
    public const CIBLE_TOUS_ADULTES = 'tous_adultes';
    public const CIBLE_CHEFS_UNITES = 'chefs_unites';
    public const CIBLE_EQUIPE_REGIONALE = 'equipe_regionale';
    public const CIBLE_CD = 'cd';
    public const CIBLE_EQUIPE_DISTRICT = 'equipe_district';
    public const CIBLE_CG = 'cg';
    public const CIBLE_MAITRISE_GROUPE = 'maitrise_groupe';
    public const CIBLE_CHEFS_OISILLONS = 'chefs_oisillons';
    public const CIBLE_CHEFS_MEUTE = 'chefs_meute';
    public const CIBLE_CHEFS_TROUPE = 'chefs_troupe';
    public const CIBLE_CHEFS_GENERATION = 'chefs_generation';
    public const CIBLE_CHEFS_COMMUNAUTE = 'chefs_communaute';
    public const CIBLE_OISILLONS = 'oisillons';
    public const CIBLE_LOUVETEAUX = 'louveteaux';
    public const CIBLE_ECLAIREURS = 'eclaireurs';
    public const CIBLE_CHEMINOTS = 'cheminots';
    public const CIBLE_ROUTIERS = 'routiers';

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

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cible = null;

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

    public function getCible(): ?string
    {
        return $this->cible;
    }

    public function setCible(string $cible): static
    {
        $this->cible = $cible;

        return $this;
    }

    /**
     * Retourne la liste des cibles disponibles pour le dropdown
     */
    public static function getCiblesDisponibles(): array
    {
        return [
            'Tous les jeunes' => self::CIBLE_TOUS_JEUNES,
            'Tous les adultes' => self::CIBLE_TOUS_ADULTES,
            "Tous les chefs d'unités" => self::CIBLE_CHEFS_UNITES,
            "Équipe régionale" => self::CIBLE_EQUIPE_REGIONALE,
            "CD" => self::CIBLE_CD,
            "Équipe de district" => self::CIBLE_EQUIPE_DISTRICT,
            "CG" => self::CIBLE_CG,
            "Maîtrise de groupe" => self::CIBLE_MAITRISE_GROUPE,
            "Chefs des oisillons" => self::CIBLE_CHEFS_OISILLONS,
            "Chefs de meute" => self::CIBLE_CHEFS_MEUTE,
            "Chefs de troupe" => self::CIBLE_CHEFS_TROUPE,
            "Chefs de génération" => self::CIBLE_CHEFS_GENERATION,
            "Chefs de communauté" => self::CIBLE_CHEFS_COMMUNAUTE,
            "Oisillons" => self::CIBLE_OISILLONS,
            "Louveteaux" => self::CIBLE_LOUVETEAUX,
            "Éclaireurs" => self::CIBLE_ECLAIREURS,
            "Cheminots" => self::CIBLE_CHEMINOTS,
            "Routiers" => self::CIBLE_ROUTIERS,
        ];
    }
}
