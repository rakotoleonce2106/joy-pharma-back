<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\Table(name: '`delivery`')]
#[Vich\Uploadable]
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
    #[Vich\UploadableField(mapping: 'delivery_residence', fileNameProperty: 'residenceDocument.name', size: 'residenceDocument.size')]
    private ?File $residenceDocumentFile = null;

    #[ORM\Embedded(class: 'Vich\\UploaderBundle\\Entity\\File')]
    #[Groups(['user:read'])]
    private ?EmbeddedFile $residenceDocument = null;

    #[Vich\UploadableField(mapping: 'delivery_vehicle', fileNameProperty: 'vehicleDocument.name', size: 'vehicleDocument.size')]
    private ?File $vehicleDocumentFile = null;

    #[ORM\Embedded(class: 'Vich\\UploaderBundle\\Entity\\File')]
    #[Groups(['user:read'])]
    private ?EmbeddedFile $vehicleDocument = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->residenceDocument = new EmbeddedFile();
        $this->vehicleDocument = new EmbeddedFile();
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

    public function setResidenceDocumentFile(?File $file = null): void
    {
        $this->residenceDocumentFile = $file;
        if ($file !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getResidenceDocumentFile(): ?File
    {
        return $this->residenceDocumentFile;
    }

    public function getResidenceDocument(): ?EmbeddedFile
    {
        return $this->residenceDocument;
    }

    public function setResidenceDocument(EmbeddedFile $file): void
    {
        $this->residenceDocument = $file;
    }

    public function setVehicleDocumentFile(?File $file = null): void
    {
        $this->vehicleDocumentFile = $file;
        if ($file !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getVehicleDocumentFile(): ?File
    {
        return $this->vehicleDocumentFile;
    }

    public function getVehicleDocument(): ?EmbeddedFile
    {
        return $this->vehicleDocument;
    }

    public function setVehicleDocument(EmbeddedFile $file): void
    {
        $this->vehicleDocument = $file;
    }
}

