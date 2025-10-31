<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\StoreSettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StoreSettingRepository::class)]
class StoreSetting
{
    use EntityIdTrait;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $mondayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $tuesdayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $wednesdayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $thursdayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $fridayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $saturdayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $sundayHours = null;

     public function __construct()
    {
        $this->initializeDefaults();
    }

    private function initializeDefaults(): void
    {
        // Initialize business hours only if they don't exist
        // Check if hours exist, if not create new BusinessHours with defaults
        // Defaults: 9-6 weekdays, 10-4 weekends, closed Sunday
        
        if (!$this->mondayHours) {
            $this->mondayHours = new BusinessHours('09:00', '18:00');
        }
        
        if (!$this->tuesdayHours) {
            $this->tuesdayHours = new BusinessHours('09:00', '18:00');
        }
        
        if (!$this->wednesdayHours) {
            $this->wednesdayHours = new BusinessHours('09:00', '18:00');
        }
        
        if (!$this->thursdayHours) {
            $this->thursdayHours = new BusinessHours('09:00', '18:00');
        }
        
        if (!$this->fridayHours) {
            $this->fridayHours = new BusinessHours('09:00', '18:00');
        }
        
        if (!$this->saturdayHours) {
            $this->saturdayHours = new BusinessHours('10:00', '16:00');
        }
        
        if (!$this->sundayHours) {
            $this->sundayHours = new BusinessHours(null, null, true); // Closed
        }
    }


    public function getTuesdayHours(): ?BusinessHours
    {
        return $this->tuesdayHours;
    }

    public function setTuesdayHours(?BusinessHours $tuesdayHours): static
    {
        $this->tuesdayHours = $tuesdayHours;

        return $this;
    }

    public function getMondayHours(): ?BusinessHours
    {
        return $this->mondayHours;
    }

    public function setMondayHours(?BusinessHours $mondayHours): static
    {
        $this->mondayHours = $mondayHours;

        return $this;
    }

    public function getWednesdayHours(): ?BusinessHours
    {
        return $this->wednesdayHours;
    }

    public function setWednesdayHours(?BusinessHours $wednesdayHours): static
    {
        $this->wednesdayHours = $wednesdayHours;

        return $this;
    }

    public function getThursdayHours(): ?BusinessHours
    {
        return $this->thursdayHours;
    }

    public function setThursdayHours(?BusinessHours $thursdayHours): static
    {
        $this->thursdayHours = $thursdayHours;

        return $this;
    }

    public function getFridayHours(): ?BusinessHours
    {
        return $this->fridayHours;
    }

    public function setFridayHours(?BusinessHours $fridayHours): static
    {
        $this->fridayHours = $fridayHours;

        return $this;
    }

    public function getSaturdayHours(): ?BusinessHours
    {
        return $this->saturdayHours;
    }

    public function setSaturdayHours(?BusinessHours $saturdayHours): static
    {
        $this->saturdayHours = $saturdayHours;

        return $this;
    }

    public function getSundayHours(): ?BusinessHours
    {
        return $this->sundayHours;
    }

    public function setSundayHours(?BusinessHours $sundayHours): static
    {
        $this->sundayHours = $sundayHours;

        return $this;
    }


}

