<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\BusinessHoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BusinessHoursRepository::class)]
class BusinessHours
{
    use EntityIdTrait;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $openTime = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $closeTime = null;

    #[ORM\Column]
    private ?bool $isClosed = null;


    /**
     * @var Collection<int, StoreSetting>
     */
    #[ORM\OneToMany(targetEntity: StoreSetting::class, mappedBy: 'tuesdayHours')]
    private Collection $tuesday;

    /**
     * @var Collection<int, StoreSetting>
     */
    #[ORM\OneToMany(targetEntity: StoreSetting::class, mappedBy: 'mondayHours')]
    private Collection $monday;

    /**
     * @var Collection<int, StoreSetting>
     */
    #[ORM\OneToMany(targetEntity: StoreSetting::class, mappedBy: 'wednesdayHours')]
    private Collection $wednesday;

    /**
     * @var Collection<int, StoreSetting>
     */
    #[ORM\OneToMany(targetEntity: StoreSetting::class, mappedBy: 'thursdayHours')]
    private Collection $thursday;

    /**
     * @var Collection<int, StoreSetting>
     */
    #[ORM\OneToMany(targetEntity: StoreSetting::class, mappedBy: 'fridayHours')]
    private Collection $friday;

    /**
     * @var Collection<int, StoreSetting>
     */
    #[ORM\OneToMany(targetEntity: StoreSetting::class, mappedBy: 'saturdayHours')]
    private Collection $saturday;

    /**
     * @var Collection<int, StoreSetting>
     */
    #[ORM\OneToMany(targetEntity: StoreSetting::class, mappedBy: 'sundayHours')]
    private Collection $sunday;

    public function __construct(?string $openTime = null, ?string $closeTime = null, bool $isClosed = false)
    {
        $this->isClosed = $isClosed;

        if (!$isClosed && $openTime && $closeTime) {
            $this->openTime = \DateTime::createFromFormat('H:i', $openTime);
            $this->closeTime = \DateTime::createFromFormat('H:i', $closeTime);
        }

        $this->tuesday = new ArrayCollection();
        $this->monday = new ArrayCollection();
        $this->wednesday = new ArrayCollection();
        $this->thursday = new ArrayCollection();
        $this->friday = new ArrayCollection();
        $this->saturday = new ArrayCollection();
        $this->sunday = new ArrayCollection();
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

     

       /**
        * @return Collection<int, StoreSetting>
        */
       public function getTuesday(): Collection
       {
           return $this->tuesday;
       }

       public function addTuesday(StoreSetting $tuesday): static
       {
           if (!$this->tuesday->contains($tuesday)) {
               $this->tuesday->add($tuesday);
               $tuesday->setTuesdayHours($this);
           }

           return $this;
       }

       public function removeTuesday(StoreSetting $tuesday): static
       {
           if ($this->tuesday->removeElement($tuesday)) {
               // set the owning side to null (unless already changed)
               if ($tuesday->getTuesdayHours() === $this) {
                   $tuesday->setTuesdayHours(null);
               }
           }

           return $this;
       }

       /**
        * @return Collection<int, StoreSetting>
        */
       public function getMonday(): Collection
       {
           return $this->monday;
       }

       public function addMonday(StoreSetting $monday): static
       {
           if (!$this->monday->contains($monday)) {
               $this->monday->add($monday);
               $monday->setMondayHours($this);
           }

           return $this;
       }

       public function removeMonday(StoreSetting $monday): static
       {
           if ($this->monday->removeElement($monday)) {
               // set the owning side to null (unless already changed)
               if ($monday->getMondayHours() === $this) {
                   $monday->setMondayHours(null);
               }
           }

           return $this;
       }

       /**
        * @return Collection<int, StoreSetting>
        */
       public function getWednesday(): Collection
       {
           return $this->wednesday;
       }

       public function addWednesday(StoreSetting $wednesday): static
       {
           if (!$this->wednesday->contains($wednesday)) {
               $this->wednesday->add($wednesday);
               $wednesday->setWednesdayHours($this);
           }

           return $this;
       }

       public function removeWednesday(StoreSetting $wednesday): static
       {
           if ($this->wednesday->removeElement($wednesday)) {
               // set the owning side to null (unless already changed)
               if ($wednesday->getWednesdayHours() === $this) {
                   $wednesday->setWednesdayHours(null);
               }
           }

           return $this;
       }

       /**
        * @return Collection<int, StoreSetting>
        */
       public function getThursday(): Collection
       {
           return $this->thursday;
       }

       public function addThursday(StoreSetting $thursday): static
       {
           if (!$this->thursday->contains($thursday)) {
               $this->thursday->add($thursday);
               $thursday->setThursdayHours($this);
           }

           return $this;
       }

       public function removeThursday(StoreSetting $thursday): static
       {
           if ($this->thursday->removeElement($thursday)) {
               // set the owning side to null (unless already changed)
               if ($thursday->getThursdayHours() === $this) {
                   $thursday->setThursdayHours(null);
               }
           }

           return $this;
       }

       /**
        * @return Collection<int, StoreSetting>
        */
       public function getFriday(): Collection
       {
           return $this->friday;
       }

       public function addFriday(StoreSetting $friday): static
       {
           if (!$this->friday->contains($friday)) {
               $this->friday->add($friday);
               $friday->setFridayHours($this);
           }

           return $this;
       }

       public function removeFriday(StoreSetting $friday): static
       {
           if ($this->friday->removeElement($friday)) {
               // set the owning side to null (unless already changed)
               if ($friday->getFridayHours() === $this) {
                   $friday->setFridayHours(null);
               }
           }

           return $this;
       }

       /**
        * @return Collection<int, StoreSetting>
        */
       public function getSaturday(): Collection
       {
           return $this->saturday;
       }

       public function addSaturday(StoreSetting $saturday): static
       {
           if (!$this->saturday->contains($saturday)) {
               $this->saturday->add($saturday);
               $saturday->setSaturdayHours($this);
           }

           return $this;
       }

       public function removeSaturday(StoreSetting $saturday): static
       {
           if ($this->saturday->removeElement($saturday)) {
               // set the owning side to null (unless already changed)
               if ($saturday->getSaturdayHours() === $this) {
                   $saturday->setSaturdayHours(null);
               }
           }

           return $this;
       }

       /**
        * @return Collection<int, StoreSetting>
        */
       public function getSunday(): Collection
       {
           return $this->sunday;
       }

       public function addSunday(StoreSetting $sunday): static
       {
           if (!$this->sunday->contains($sunday)) {
               $this->sunday->add($sunday);
               $sunday->setSundayHours($this);
           }

           return $this;
       }

       public function removeSunday(StoreSetting $sunday): static
       {
           if ($this->sunday->removeElement($sunday)) {
               // set the owning side to null (unless already changed)
               if ($sunday->getSundayHours() === $this) {
                   $sunday->setSundayHours(null);
               }
           }

           return $this;
       }
}
