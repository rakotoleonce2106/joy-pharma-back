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

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $mondayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $tuesdayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $wednesdayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $thursdayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $fridayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $saturdayHours = null;

    #[ORM\ManyToOne(targetEntity: BusinessHours::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['store_setting:read', 'store_setting:write'])]
    private ?BusinessHours $sundayHours = null;

    public function __construct()
    {
        $this->initializeDefaults();
    }

    public function initializeDefaults(): void
    {
        // Initialize business hours only if they don't exist
        // Check if hours exist, if not create new BusinessHours with defaults
        // Defaults: Monday-Friday 8:00-17:00, Saturday-Sunday closed
        // IMPORTANT: Create a new instance for each day to avoid shared references

        if (!$this->mondayHours) {
            $this->mondayHours = new BusinessHours('08:00', '17:00');
        }

        if (!$this->tuesdayHours) {
            $this->tuesdayHours = new BusinessHours('08:00', '17:00');
        }

        if (!$this->wednesdayHours) {
            $this->wednesdayHours = new BusinessHours('08:00', '17:00');
        }

        if (!$this->thursdayHours) {
            $this->thursdayHours = new BusinessHours('08:00', '17:00');
        }

        if (!$this->fridayHours) {
            $this->fridayHours = new BusinessHours('08:00', '17:00');
        }

        if (!$this->saturdayHours) {
            $this->saturdayHours = new BusinessHours(null, null, true);
        }

        if (!$this->sundayHours) {
            $this->sundayHours = new BusinessHours(null, null, true);
        }
    }

    /**
     * Get BusinessHours for a specific day, ensuring it's never null
     */
    private function getBusinessHoursSafe(string $property): BusinessHours
    {
        $getter = 'get' . ucfirst($property);
        $hours = $this->$getter();
        
        if (!$hours) {
            // Create default if null
            if ($property === 'sundayHours' || $property === 'saturdayHours') {
                $hours = new BusinessHours(null, null, true);
            } else {
                $hours = new BusinessHours('08:00', '17:00', false);
            }
            $setter = 'set' . ucfirst($property);
            $this->$setter($hours);
        }
        
        return $hours;
    }


    public function getTuesdayHours(): ?BusinessHours
    {
        if (!$this->tuesdayHours) {
            $this->tuesdayHours = new BusinessHours('08:00', '17:00', false);
        }
        return $this->tuesdayHours;
    }

    public function setTuesdayHours(?BusinessHours $tuesdayHours): static
    {
        $this->tuesdayHours = $tuesdayHours;

        return $this;
    }

    public function getMondayHours(): ?BusinessHours
    {
        if (!$this->mondayHours) {
            $this->mondayHours = new BusinessHours('08:00', '17:00', false);
        }
        return $this->mondayHours;
    }

    public function setMondayHours(?BusinessHours $mondayHours): static
    {
        $this->mondayHours = $mondayHours;

        return $this;
    }

    public function getWednesdayHours(): ?BusinessHours
    {
        if (!$this->wednesdayHours) {
            $this->wednesdayHours = new BusinessHours('08:00', '17:00', false);
        }
        return $this->wednesdayHours;
    }

    public function setWednesdayHours(?BusinessHours $wednesdayHours): static
    {
        $this->wednesdayHours = $wednesdayHours;

        return $this;
    }

    public function getThursdayHours(): ?BusinessHours
    {
        if (!$this->thursdayHours) {
            $this->thursdayHours = new BusinessHours('08:00', '17:00', false);
        }
        return $this->thursdayHours;
    }

    public function setThursdayHours(?BusinessHours $thursdayHours): static
    {
        $this->thursdayHours = $thursdayHours;

        return $this;
    }

    public function getFridayHours(): ?BusinessHours
    {
        if (!$this->fridayHours) {
            $this->fridayHours = new BusinessHours('08:00', '17:00', false);
        }
        return $this->fridayHours;
    }

    public function setFridayHours(?BusinessHours $fridayHours): static
    {
        $this->fridayHours = $fridayHours;

        return $this;
    }

    public function getSaturdayHours(): ?BusinessHours
    {
        if (!$this->saturdayHours) {
            $this->saturdayHours = new BusinessHours(null, null, true); // Closed
        }
        return $this->saturdayHours;
    }

    public function setSaturdayHours(?BusinessHours $saturdayHours): static
    {
        $this->saturdayHours = $saturdayHours;

        return $this;
    }

    public function getSundayHours(): ?BusinessHours
    {
        if (!$this->sundayHours) {
            $this->sundayHours = new BusinessHours(null, null, true); // Closed
        }
        return $this->sundayHours;
    }

    public function setSundayHours(?BusinessHours $sundayHours): static
    {
        $this->sundayHours = $sundayHours;

        return $this;
    }
}
