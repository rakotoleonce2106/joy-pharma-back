<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\BusinessHoursRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BusinessHoursRepository::class)]
class BusinessHours
{
    use EntityIdTrait;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write', 'business_hours:read', 'business_hours:write'])]
    private ?string $openTime = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write', 'business_hours:read', 'business_hours:write'])]
    private ?string $closeTime = null;

    #[ORM\Column]
    #[Groups(['store_setting:read', 'store_setting:write', 'business_hours:read', 'business_hours:write'])]
    private ?bool $isClosed = null;



    public function __construct(?string $openTime = null, ?string $closeTime = null, bool $isClosed = false)
    {
        $this->openTime = $openTime;
        $this->closeTime = $closeTime;
        $this->isClosed = $isClosed;
    }

    public function getOpenTime(): ?string
    {
        return $this->openTime;
    }

    public function getCloseTime(): ?string
    {
        return $this->closeTime;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function isOpen(\DateTimeInterface $time): bool
    {
        if ($this->isClosed || !$this->openTime || !$this->closeTime) {
            return false;
        }

        $currentTime = $time->format('H:i');
        
        // Ensure format is HH:mm for comparison
        $openTime = $this->formatToShortTime($this->openTime);
        $closeTime = $this->formatToShortTime($this->closeTime);

        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    private function formatToShortTime(?string $time): ?string
    {
        if (!$time) return null;
        if (preg_match('/^\d{2}:\d{2}$/', $time)) return $time;
        if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $time, $matches)) return $matches[1];
        
        try {
            return (new \DateTime($time))->format('H:i');
        } catch (\Exception $e) {
            return $time;
        }
    }


    public function setOpenTime(?string $openTime): static
    {
        $this->openTime = $openTime;

        return $this;
    }


    public function setCloseTime(?string $closeTime): static
    {
        $this->closeTime = $closeTime;

        return $this;
    }


    public function setIsClosed(bool $isClosed): static
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    public function getFormattedHours(): string
    {
        if ($this->isClosed) {
            return 'Closed';
        }

        if (!$this->openTime || !$this->closeTime) {
            return '24/7';
        }

        return sprintf('%s - %s', 
            $this->formatToShortTime($this->openTime), 
            $this->formatToShortTime($this->closeTime)
        );
    }
}
