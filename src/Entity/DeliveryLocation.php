<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\DeliveryLocationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DeliveryLocationRepository::class)]
#[ORM\Table(name: '`delivery_location`')]
class DeliveryLocation
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['location:read'])]
    private ?User $deliveryPerson = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8)]
    #[Groups(['location:read', 'location:write'])]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8)]
    #[Groups(['location:read', 'location:write'])]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['location:read', 'location:write'])]
    private ?float $accuracy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['location:read', 'location:write'])]
    private ?\DateTimeInterface $timestamp = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->timestamp = new \DateTime();
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

    public function getAccuracy(): ?float
    {
        return $this->accuracy;
    }

    public function setAccuracy(?float $accuracy): static
    {
        $this->accuracy = $accuracy;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}


