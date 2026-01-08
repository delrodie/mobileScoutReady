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

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $fcmToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $devicePlatform = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceModel = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $fcmTokenUpdatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $deviceVerified = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $deviceVerificationOtp = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deviceVerificationOtpExpiry = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $previousFcmToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pendingDeviceId = null;

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

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(?string $fcmToken): static
    {
        $this->fcmToken = $fcmToken;
        $this->fcmTokenUpdatedAt = new \DateTimeImmutable();

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

    public function getFcmTokenUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->fcmTokenUpdatedAt;
    }

    public function setFcmTokenUpdatedAt(?\DateTimeImmutable $fcmTokenUpdatedAt): static
    {
        $this->fcmTokenUpdatedAt = $fcmTokenUpdatedAt;

        return $this;
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

    public function getDeviceVerificationOtp(): ?string
    {
        return $this->deviceVerificationOtp;
    }

    public function setDeviceVerificationOtp(?string $deviceVerificationOtp): static
    {
        $this->deviceVerificationOtp = $deviceVerificationOtp;

        return $this;
    }

    public function getDeviceVerificationOtpExpiry(): ?\DateTimeImmutable
    {
        return $this->deviceVerificationOtpExpiry;
    }

    public function setDeviceVerificationOtpExpiry(?\DateTimeImmutable $deviceVerificationOtpExpiry): static
    {
        $this->deviceVerificationOtpExpiry = $deviceVerificationOtpExpiry;

        return $this;
    }

    public function getPreviousFcmToken(): ?string
    {
        return $this->previousFcmToken;
    }

    public function setPreviousFcmToken(?string $previousFcmToken): static
    {
        $this->previousFcmToken = $previousFcmToken;

        return $this;
    }

    public function getPendingDeviceId(): ?string
    {
        return $this->pendingDeviceId;
    }

    public function setPendingDeviceId(?string $pendingDeviceId): static
    {
        $this->pendingDeviceId = $pendingDeviceId;

        return $this;
    }

    public function isDeviceOptValid(string $otp): bool
    {
        if (!$this->deviceVerificationOtp || !$this->deviceVerificationOtpExpiry) {
            return false;
        }

        if (new \DateTimeImmutable() > $this->deviceVerificationOtpExpiry){
            return false;
        }

        return $this->deviceVerificationOtp === $otp;
    }
}
