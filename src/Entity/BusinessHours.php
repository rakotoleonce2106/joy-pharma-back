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

    #[ORM\Column(type: 'time', nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?\DateTimeInterface $openTime = null;

    #[ORM\Column(type: 'time', nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?\DateTimeInterface $closeTime = null;

    #[ORM\Column]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?bool $isClosed = null;



    public function __construct($openTime = null, $closeTime = null, bool $isClosed = false)
    {
        $this->isClosed = $isClosed;

        if (!$isClosed && $openTime && $closeTime) {
            // Handle string format (for manual creation)
            if (is_string($openTime)) {
                $this->openTime = \DateTime::createFromFormat('H:i', $openTime);
            } elseif ($openTime instanceof \DateTimeInterface) {
                $this->openTime = $openTime;
            }
            
            // Handle string format (for manual creation)
            if (is_string($closeTime)) {
                $this->closeTime = \DateTime::createFromFormat('H:i', $closeTime);
            } elseif ($closeTime instanceof \DateTimeInterface) {
                $this->closeTime = $closeTime;
            }
        }
    }

    public function getOpenTime(): ?\DateTimeInterface
    {
        return $this->openTime;
    }

    public function getCloseTime(): ?\DateTimeInterface
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
        $openTime = $this->openTime->format('H:i');
        $closeTime = $this->closeTime->format('H:i');

        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }


    public function setOpenTime(?\DateTime $openTime): static
    {
        $this->openTime = $openTime;

        return $this;
    }


    public function setCloseTime(?\DateTime $closeTime): static
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
            $this->openTime->format('H:i'), 
            $this->closeTime->format('H:i')
        );
    }
}
