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
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['qr_scan_log:read'])]
    private ?Store $store = null;

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
}

