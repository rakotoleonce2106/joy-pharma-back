<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\EmergencySOSRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

enum SOSStatus: string
{
    case ACTIVE = 'active';
    case RESOLVED = 'resolved';
    case FALSE_ALARM = 'false_alarm';
}

#[ORM\Entity(repositoryClass: EmergencySOSRepository::class)]
#[ORM\Table(name: '`emergency_sos`')]
class EmergencySOS
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sos:read'])]
    private ?User $deliveryPerson = null;

    #[ORM\ManyToOne]
    #[Groups(['sos:read'])]
    private ?Order $orderRef = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8)]
    #[Groups(['sos:read', 'sos:write'])]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8)]
    #[Groups(['sos:read', 'sos:write'])]
    private ?string $longitude = null;

    #[ORM\Column(type: 'string', length: 20, enumType: SOSStatus::class)]
    #[Groups(['sos:read'])]
    private SOSStatus $status;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['sos:read'])]
    private ?\DateTimeInterface $resolvedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['sos:read', 'sos:write'])]
    private ?string $notes = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = SOSStatus::ACTIVE;
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

    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(?Order $orderRef): static
    {
        $this->orderRef = $orderRef;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getStatus(): SOSStatus
    {
        return $this->status;
    }

    public function setStatus(SOSStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeInterface
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeInterface $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }
}


