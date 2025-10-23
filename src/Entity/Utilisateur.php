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
}
