<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\DeliveryScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DeliveryScheduleRepository::class)]
#[ORM\Table(name: '`delivery_schedule`')]
class DeliverySchedule
{
    use EntityIdTrait;

    #[ORM\ManyToOne(inversedBy: 'deliverySchedules')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['schedule:read'])]
    private ?User $deliveryPerson = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['schedule:read', 'schedule:write'])]
    private int $dayOfWeek; // 0 = Sunday, 6 = Saturday

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['schedule:read', 'schedule:write'])]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['schedule:read', 'schedule:write'])]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['schedule:read', 'schedule:write'])]
    private bool $isActive = true;

    public function getDeliveryPerson(): ?User
    {
        return $this->deliveryPerson;
    }

    public function setDeliveryPerson(?User $deliveryPerson): static
    {
        $this->deliveryPerson = $deliveryPerson;
        return $this;
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }
}

