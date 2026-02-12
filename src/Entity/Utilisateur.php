<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
//#[Broadcast]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?Scout $scout = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telegramChatId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $otpCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $otpRequestedAt = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $role = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalConnexion = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastConnectedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastConnectedDevice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastConnectedIp = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $devicePlatform = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceModel = null;



    #[ORM\Column(length: 4, nullable: true)]
    private ?string $pinCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $pinCodeCreatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $pinCodeUpdatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $deviceVerified = null;

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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getTelegramChatId(): ?string
    {
        return $this->telegramChatId;
    }

    public function setTelegramChatId(?string $telegramChatId): static
    {
        $this->telegramChatId = $telegramChatId;

        return $this;
    }

    public function getOtpCode(): ?string
    {
        return $this->otpCode;
    }

    public function setOtpCode(?string $otpCode): static
    {
        $this->otpCode = $otpCode;

        return $this;
    }

    public function getOtpRequestedAt(): ?\DateTimeImmutable
    {
        return $this->otpRequestedAt;
    }

    public function setOtpRequestedAt(?\DateTimeImmutable $otpRequestedAt): static
    {
        $this->otpRequestedAt = $otpRequestedAt;

        return $this;
    }

    public function getRole(): ?array
    {
        return $this->role;
    }

    public function setRole(?array $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getTotalConnexion(): ?int
    {
        return $this->totalConnexion;
    }

    public function setTotalConnexion(?int $totalConnexion): static
    {
        $this->totalConnexion = $totalConnexion;

        return $this;
    }

    public function getLastConnectedAt(): ?\DateTimeImmutable
    {
        return $this->lastConnectedAt;
    }

    public function setLastConnectedAt(?\DateTimeImmutable $lastConnectedAt): static
    {
        $this->lastConnectedAt = $lastConnectedAt;

        return $this;
    }

    public function getLastConnectedDevice(): ?string
    {
        return $this->lastConnectedDevice;
    }

    public function setLastConnectedDevice(?string $lastConnectedDevice): static
    {
        $this->lastConnectedDevice = $lastConnectedDevice;

        return $this;
    }

    public function getLastConnectedIp(): ?string
    {
        return $this->lastConnectedIp;
    }

    public function setLastConnectedIp(?string $lastConnectedIp): static
    {
        $this->lastConnectedIp = $lastConnectedIp;

        return $this;
    }



    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    public function setDeviceId(?string $deviceId): static
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    public function getDevicePlatform(): ?string
    {
        return $this->devicePlatform;
    }

    public function setDevicePlatform(?string $devicePlatform): static
    {
        $this->devicePlatform = $devicePlatform;

        return $this;
    }

    public function getDeviceModel(): ?string
    {
        return $this->deviceModel;
    }

    public function setDeviceModel(?string $deviceModel): static
    {
        $this->deviceModel = $deviceModel;

        return $this;
    }


    public function getPinCode(): ?string
    {
        return $this->pinCode;
    }

    public function setPinCode(?string $pinCode): static
    {
        $this->pinCode = $pinCode;

        return $this;
    }

    public function getPinCodeCreatedAt(): ?\DateTimeImmutable
    {
        return $this->pinCodeCreatedAt;
    }

    public function setPinCodeCreatedAt(?\DateTimeImmutable $pinCodeCreatedAt): static
    {
        $this->pinCodeCreatedAt = $pinCodeCreatedAt;

        return $this;
    }

    public function getPinCodeUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->pinCodeUpdatedAt;
    }

    public function setPinCodeUpdatedAt(?\DateTimeImmutable $pinCodeUpdatedAt): static
    {
        $this->pinCodeUpdatedAt = $pinCodeUpdatedAt;

        return $this;
    }

    /**
     * Vérifie si le PIN est correct
     */
    public function verifyPin(string $pin): bool
    {
        return $this->pinCode === $pin;
    }

    /**
     * Vérifie si l'utilisateur à configurer un PIN
     */
    public function hasPinCode(): bool
    {
        return !empty($this->pinCode);
    }

    public function isDeviceVerified(): ?bool
    {
        return $this->deviceVerified;
    }

    public function setDeviceVerified(?bool $deviceVerified): static
    {
        $this->deviceVerified = $deviceVerified;

        return $this;
    }
}
