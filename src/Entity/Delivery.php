<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: '`delivery`')]
class Delivery
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\OneToOne(inversedBy: 'delivery', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?User $user = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['user:read', 'user:update'])]
    private bool $isOnline = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $currentLatitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $currentLongitude = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $lastLocationUpdate = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['user:read'])]
    private int $totalDeliveries = 0;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['user:read'])]
    private ?float $averageRating = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0])]
    #[Groups(['user:read'])]
    private string $totalEarnings = '0.00';

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['user:read', 'user:update'])]
    private ?string $vehicleType = null; // bike, motorcycle, car

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['user:read', 'user:update'])]
    private ?string $vehiclePlate = null;

    // Delivery verification documents
    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read'])]
    #[ApiProperty(types: ['https://schema.org/Document'])]
    private ?MediaObject $residenceDocument = null;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read'])]
    #[ApiProperty(types: ['https://schema.org/Document'])]
    private ?MediaObject $vehicleDocument = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getIsOnline(): bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;
        return $this;
    }

    public function getCurrentLatitude(): ?string
    {
        return $this->currentLatitude;
    }

    public function setCurrentLatitude(?string $currentLatitude): static
    {
        $this->currentLatitude = $currentLatitude;
        return $this;
    }

    public function getCurrentLongitude(): ?string
    {
        return $this->currentLongitude;
    }

    public function setCurrentLongitude(?string $currentLongitude): static
    {
        $this->currentLongitude = $currentLongitude;
        return $this;
    }

    public function getLastLocationUpdate(): ?\DateTimeInterface
    {
        return $this->lastLocationUpdate;
    }

    public function setLastLocationUpdate(?\DateTimeInterface $lastLocationUpdate): static
    {
        $this->lastLocationUpdate = $lastLocationUpdate;
        return $this;
    }

    public function getTotalDeliveries(): int
    {
        return $this->totalDeliveries;
    }

    public function setTotalDeliveries(int $totalDeliveries): static
    {
        $this->totalDeliveries = $totalDeliveries;
        return $this;
    }

    public function incrementTotalDeliveries(): static
    {
        $this->totalDeliveries++;
        return $this;
    }

    public function getAverageRating(): ?float
    {
        return $this->averageRating;
    }

    public function setAverageRating(?float $averageRating): static
    {
        $this->averageRating = $averageRating;
        return $this;
    }

    public function getTotalEarnings(): string
    {
        return $this->totalEarnings;
    }

    public function setTotalEarnings(string $totalEarnings): static
    {
        $this->totalEarnings = $totalEarnings;
        return $this;
    }

    public function addEarnings(float $amount): static
    {
        $this->totalEarnings = bcadd($this->totalEarnings, (string)$amount, 2);
        return $this;
    }

    public function getVehicleType(): ?string
    {
        return $this->vehicleType;
    }

    public function setVehicleType(?string $vehicleType): static
    {
        $this->vehicleType = $vehicleType;
        return $this;
    }

    public function getVehiclePlate(): ?string
    {
        return $this->vehiclePlate;
    }

    public function setVehiclePlate(?string $vehiclePlate): static
    {
        $this->vehiclePlate = $vehiclePlate;
        return $this;
    }

    public function getResidenceDocument(): ?MediaObject
    {
        return $this->residenceDocument;
    }

    public function setResidenceDocument(?MediaObject $residenceDocument): static
    {
        $this->residenceDocument = $residenceDocument;

        return $this;
    }

    public function getVehicleDocument(): ?MediaObject
    {
        return $this->vehicleDocument;
    }

    public function setVehicleDocument(?MediaObject $vehicleDocument): static
    {
        $this->vehicleDocument = $vehicleDocument;

        return $this;
    }
}

