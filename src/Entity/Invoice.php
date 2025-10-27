<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: '`invoice`')]
class Invoice
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['invoice:read'])]
    private ?User $deliveryPerson = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['invoice:read'])]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['invoice:read'])]
    private ?\DateTimeInterface $periodStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['invoice:read'])]
    private ?\DateTimeInterface $periodEnd = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read'])]
    private ?string $totalEarnings = null;

    #[ORM\Column]
    #[Groups(['invoice:read'])]
    private int $totalDeliveries = 0;

    #[ORM\Column(type: 'string', length: 20, enumType: InvoiceStatus::class)]
    #[Groups(['invoice:read'])]
    private InvoiceStatus $status;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['invoice:read'])]
    private ?string $pdfPath = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = InvoiceStatus::PENDING;
        $this->reference = $this->generateReference();
    }

    private function generateReference(): string
    {
        return 'INV-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function getDeliveryPerson(): ?User
    {
        return $this->deliveryPerson;
    }

    public function setDeliveryPerson(?User $deliveryPerson): static
    {
        $this->deliveryPerson = $deliveryPerson;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getPeriodStart(): ?\DateTimeInterface
    {
        return $this->periodStart;
    }

    public function setPeriodStart(\DateTimeInterface $periodStart): static
    {
        $this->periodStart = $periodStart;
        return $this;
    }

    public function getPeriodEnd(): ?\DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(\DateTimeInterface $periodEnd): static
    {
        $this->periodEnd = $periodEnd;
        return $this;
    }

    public function getTotalEarnings(): ?string
    {
        return $this->totalEarnings;
    }

    public function setTotalEarnings(string $totalEarnings): static
    {
        $this->totalEarnings = $totalEarnings;
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

    public function getStatus(): InvoiceStatus
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;
        return $this;
    }
}


