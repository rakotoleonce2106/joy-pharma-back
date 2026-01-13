<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\QrScanLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: QrScanLogRepository::class)]
#[ORM\Table(name: 'qr_scan_log')]
#[ORM\HasLifecycleCallbacks]
class QrScanLog
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['qr_scan_log:read'])]
    private ?User $agent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['qr_scan_log:read'])]
    private ?Store $store = null; // For store pickup scans

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['qr_scan_log:read'])]
    private ?User $customer = null; // For customer delivery scans

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['qr_scan_log:read'])]
    private ?Order $order = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['qr_scan_log:read'])]
    private ?string $scannedQrCode = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['qr_scan_log:read'])]
    private bool $success = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['qr_scan_log:read'])]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['qr_scan_log:read'])]
    private ?\DateTimeInterface $scannedAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    #[Groups(['qr_scan_log:read'])]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    #[Groups(['qr_scan_log:read'])]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Groups(['qr_scan_log:read'])]
    private ?string $scanType = null; // 'store_pickup' or 'customer_delivery'

    public function __construct()
    {
        $this->scannedAt = new \DateTime();
        $this->success = false;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): static
    {
        $this->store = $store;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getScannedQrCode(): ?string
    {
        return $this->scannedQrCode;
    }

    public function setScannedQrCode(string $scannedQrCode): static
    {
        $this->scannedQrCode = $scannedQrCode;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): static
    {
        $this->success = $success;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getScannedAt(): ?\DateTimeInterface
    {
        return $this->scannedAt;
    }

    public function setScannedAt(?\DateTimeInterface $scannedAt): static
    {
        $this->scannedAt = $scannedAt;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getScanType(): ?string
    {
        return $this->scanType;
    }

    public function setScanType(?string $scanType): static
    {
        $this->scanType = $scanType;

        return $this;
    }
}

