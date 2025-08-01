<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityStatusTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\StoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;



enum BoutiqueStatus: string
{
    case PENDING = 'pending';           // Waiting for approval
    case ACTIVE = 'active';             // Active and selling
    case SUSPENDED = 'suspended';       // Temporarily suspended
    case INACTIVE = 'inactive';         // Voluntarily inactive
    case BANNED = 'banned';             // Permanently banned
    case UNDER_REVIEW = 'under_review'; // Under admin review

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::INACTIVE => 'Inactive',
            self::BANNED => 'Banned',
            self::UNDER_REVIEW => 'Under Review',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canReceiveOrders(): bool
    {
        return $this === self::ACTIVE;
    }
}

#[ORM\Entity(repositoryClass: StoreRepository::class)]
#[ApiResource]
class Store
{
    use EntityIdTrait;
    use EntityStatusTrait;
    use EntityTimestampTrait;
    

    #[ORM\Column(length: 255)]
    private ?string $name = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, MediaFile>
     */
    #[ORM\OneToMany(targetEntity: MediaFile::class, mappedBy: 'store')]
    private Collection $image;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'store')]
    private Collection $owner;


    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?StoreSetting $setting = null;


    #[ORM\OneToOne(inversedBy: 'store', cascade: ['persist', 'remove'])]
    private ?ContactInfo $contact = null;

    #[ORM\OneToOne(inversedBy: 'store', cascade: ['persist', 'remove'])]
    private ?Location $location = null;

    public function __construct()
    {
        $this->owner = new ArrayCollection();
        $this->image = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getOwner(): Collection
    {
        return $this->owner;
    }

    public function addOwner(User $owner): static
    {
        if (!$this->owner->contains($owner)) {
            $this->owner->add($owner);
            $owner->setStore($this);
        }

        return $this;
    }

    public function removeOwner(User $owner): static
    {
        if ($this->owner->removeElement($owner)) {
            // set the owning side to null (unless already changed)
            if ($owner->getStore() === $this) {
                $owner->setStore(null);
            }
        }

        return $this;
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, MediaFile>
     */
    public function getImage(): Collection
    {
        return $this->image;
    }

    public function addImage(MediaFile $image): static
    {
        if (!$this->image->contains($image)) {
            $this->image->add($image);
            $image->setStore($this);
        }

        return $this;
    }

    public function removeImage(MediaFile $image): static
    {
        if ($this->image->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getStore() === $this) {
                $image->setStore(null);
            }
        }

        return $this;
    }

    public function getSetting(): ?StoreSetting
    {
        return $this->setting;
    }

    public function setSetting(?StoreSetting $setting): static
    {
        $this->setting = $setting;

        return $this;
    }

   

    public function getContact(): ?ContactInfo
    {
        return $this->contact;
    }

    public function setContact(?ContactInfo $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }
}
