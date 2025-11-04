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
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

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
#[Vich\Uploadable]
class Store
{
    use EntityIdTrait;
    use EntityStatusTrait;
    use EntityTimestampTrait;
    

    #[ORM\Column(length: 255)]
    #[Groups(['store:read'])]
    private ?string $name = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['store:read'])]
    private ?string $description = null;

    #[Vich\UploadableField(mapping: 'store', fileNameProperty: 'image.name', size: 'image.size')]
    #[Groups(['store:create', 'store:update'])]
    private ?File $imageFile = null;

    #[ORM\Embedded(class: 'Vich\UploaderBundle\Entity\File')]
    #[Groups(['store:read'])]
    private ?EmbeddedFile $image = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(['store:read'])]
    private ?StoreSetting $setting = null;


    #[ORM\OneToOne(inversedBy: 'store', cascade: ['persist', 'remove'])]
    #[Groups(['store:read'])]
    private ?ContactInfo $contact = null;

    #[ORM\OneToOne(inversedBy: 'store', cascade: ['persist', 'remove'])]
    #[Groups(['store:read'])]
    private ?Location $location = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?User $owner = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    private Collection $categories;

    /**
     * @var Collection<int, StoreProduct>
     */
    #[ORM\OneToMany(targetEntity: StoreProduct::class, mappedBy: 'store')]
    private Collection $storeProducts;

    #[ORM\ManyToOne(inversedBy: 'store')]
    private ?OrderItem $orderItem = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Groups(['store:read'])]
    private ?string $qrCode = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->image = new EmbeddedFile();
        $this->categories = new ArrayCollection();
        $this->storeProducts = new ArrayCollection();
        $this->qrCode = $this->generateQRCode();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImage(EmbeddedFile $image): void
    {
        $this->image = $image;
    }

    public function getImage(): ?EmbeddedFile
    {
        return $this->image;
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, StoreProduct>
     */
    public function getStoreProducts(): Collection
    {
        return $this->storeProducts;
    }

    public function addStoreProduct(StoreProduct $storeProduct): static
    {
        if (!$this->storeProducts->contains($storeProduct)) {
            $this->storeProducts->add($storeProduct);
            $storeProduct->setStore($this);
        }

        return $this;
    }

    public function removeStoreProduct(StoreProduct $storeProduct): static
    {
        if ($this->storeProducts->removeElement($storeProduct)) {
            // set the owning side to null (unless already changed)
            if ($storeProduct->getStore() === $this) {
                $storeProduct->setStore(null);
            }
        }

        return $this;
    }

    public function getOrderItem(): ?OrderItem
    {
        return $this->orderItem;
    }

    public function setOrderItem(?OrderItem $orderItem): static
    {
        $this->orderItem = $orderItem;

        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): static
    {
        $this->qrCode = $qrCode;

        return $this;
    }

    /**
     * Generate a unique QR code for the store
     */
    private function generateQRCode(): string
    {
        return 'STORE-' . strtoupper(bin2hex(random_bytes(16)));
    }
}
