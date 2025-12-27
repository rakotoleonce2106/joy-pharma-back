<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\BusinessHoursRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BusinessHoursRepository::class)]
class BusinessHours
{
    use EntityIdTrait;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $openTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closeTime = null;

    #[ORM\Column]
    #[Groups(['store_setting:read', 'store_setting:write', 'business_hours:read', 'business_hours:write'])]
    private ?bool $isClosed = null;



    public function __construct(string|\DateTimeInterface|null $openTime = null, string|\DateTimeInterface|null $closeTime = null, bool $isClosed = false)
    {
        $this->openTime = $this->parseTime($openTime);
        $this->closeTime = $this->parseTime($closeTime);
        $this->isClosed = $isClosed;
    }

    /**
     * Parse time from string or DateTimeInterface to DateTimeInterface
     */
    private function parseTime(string|\DateTimeInterface|null $time): ?\DateTimeInterface
    {
        if ($time === null) {
            return null;
        }

        if ($time instanceof \DateTimeInterface) {
            return $time;
        }

        // Parse string format HH:MM or HH:MM:SS
        if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $time, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];
            $second = isset($matches[3]) ? (int)$matches[3] : 0;
            
            $dateTime = new \DateTime();
            $dateTime->setTime($hour, $minute, $second);
            return $dateTime;
        }

        // Try to parse as DateTime
        try {
            return new \DateTime($time);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getOpenTime(): ?\DateTimeInterface
    {
        return $this->openTime;
    }

    /**
     * Get open time as formatted string (HH:MM) for API serialization
     */
    #[Groups(['store_setting:read', 'store_setting:write', 'business_hours:read', 'business_hours:write'])]
    public function getOpenTimeFormatted(): ?string
    {
        return $this->openTime ? $this->openTime->format('H:i') : null;
    }

    public function getCloseTime(): ?\DateTimeInterface
    {
        return $this->closeTime;
    }

    /**
     * Get close time as formatted string (HH:MM) for API serialization
     */
    #[Groups(['store_setting:read', 'store_setting:write', 'business_hours:read', 'business_hours:write'])]
    public function getCloseTimeFormatted(): ?string
    {
        return $this->closeTime ? $this->closeTime->format('H:i') : null;
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
        $openTime = $this->openTime->format('H:i');
        $closeTime = $this->closeTime->format('H:i');

        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    public function setOpenTime(string|\DateTimeInterface|null $openTime): static
    {
        $this->openTime = $this->parseTime($openTime);

        return $this;
    }

    /**
     * Set open time from formatted string (HH:MM) for API deserialization
     */
    public function setOpenTimeFormatted(?string $openTime): static
    {
        $this->openTime = $this->parseTime($openTime);

        return $this;
    }

    public function setCloseTime(string|\DateTimeInterface|null $closeTime): static
    {
        $this->closeTime = $this->parseTime($closeTime);

        return $this;
    }

    /**
     * Set close time from formatted string (HH:MM) for API deserialization
     */
    public function setCloseTimeFormatted(?string $closeTime): static
    {
        $this->closeTime = $this->parseTime($closeTime);

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
            $this->openTime->format('H:i'), 
            $this->closeTime->format('H:i')
        );
    }
}
