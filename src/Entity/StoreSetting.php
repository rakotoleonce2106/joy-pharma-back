<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\StoreSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StoreSettingRepository::class)]
class StoreSetting
{
    use EntityIdTrait;

    #[ORM\ManyToOne(inversedBy: 'monday')]
    private ?BusinessHours $mondayHours = null;

    #[ORM\ManyToOne(inversedBy: 'tuesday')]
    private ?BusinessHours $tuesdayHours = null;

    #[ORM\ManyToOne(inversedBy: 'wednesday')]
    private ?BusinessHours $wednesdayHours = null;

    #[ORM\ManyToOne(inversedBy: 'thursday')]
    private ?BusinessHours $thursdayHours = null;

    #[ORM\ManyToOne(inversedBy: 'friday')]
    private ?BusinessHours $fridayHours = null;

    #[ORM\ManyToOne(inversedBy: 'saturday')]
    private ?BusinessHours $saturdayHours = null;

    #[ORM\ManyToOne(inversedBy: 'sunday')]
    private ?BusinessHours $sundayHours = null;

     public function __construct()
    {
        $this->initializeDefaults();
    }

        private function initializeDefaults(): void
    {
        // Initialize business hours (9-6 weekdays, 10-4 weekends, closed Sunday)
        $this->mondayHours = new BusinessHours('09:00', '18:00');
        $this->tuesdayHours = new BusinessHours('09:00', '18:00');
        $this->wednesdayHours = new BusinessHours('09:00', '18:00');
        $this->thursdayHours = new BusinessHours('09:00', '18:00');
        $this->fridayHours = new BusinessHours('09:00', '18:00');
        $this->saturdayHours = new BusinessHours('10:00', '16:00');
        $this->sundayHours = new BusinessHours(null, null, true); // Closed


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

