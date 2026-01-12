<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

enum OrderStatus :string
{
    case STATUS_PENDING = 'pending';
    case STATUS_CONFIRMED = 'confirmed';
    case STATUS_PROCESSING = 'processing';
    case STATUS_SHIPPED = 'shipped';
    case STATUS_COLLECTED = 'collected'; // Recuperée - Order picked up from store
    case STATUS_DELIVERED = 'delivered';
    case STATUS_CANCELLED = 'cancelled';
}

enum PriorityType : string
{
     case PRIORITY_URGENT = 'urgent';
     case PRIORITY_STANDARD = 'standard';
     case PRIORITY_PLANIFIED = 'planified';

}

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '"order"')]
#[ORM\HasLifecycleCallbacks]
class Order
{

    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Customer is required')]
    #[Groups(['order:create','order:read','order:write'])]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'orders', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['order:create','order:read','order:write'])]
    private ?Location $location = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['order:create','order:read','order:write'])]
    #[Assert\GreaterThanOrEqual('today', message: 'Scheduled date cannot be in the past')]
    private ?\DateTimeInterface $scheduledDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['order:create','order:read','order:write'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 20, enumType: OrderStatus::class)]
    #[Groups(['order:create','order:read','order:write', 'payment:order:read'])]
    private OrderStatus $status;

    #[ORM\Column(type: 'string', length: 20, enumType: PriorityType::class)]
    #[Groups(['order:create','order:read','order:write', 'payment:order:read'])]
    private PriorityType $priority;

    /**
     * @var Collection<int, OrderItem>
     * 
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'orderParent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['order:create','order:read','order:write', 'payment:order:read'])]
    private Collection $items;

    #[ORM\Column]
    #[Groups(['order:read', 'payment:order:read'])]
    private ?float $totalAmount = 0.0;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read', 'payment:order:read'])]
    private ?float $storeTotalAmount = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read','order:write', 'payment:order:read'])]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Phone number is required', groups: ['create'])]
    #[Groups(['order:read','order:write', 'payment:order:read'])]
    private ?string $phone = null;

    #[ORM\ManyToOne(inversedBy: 'orders', cascade: ['persist', 'remove'])]
    #[Groups(['order:read'])]
    // Payment exclu du groupe payment:order:read pour éviter la récursion
    private ?Payment $payment = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['order:read'])]
    private ?Promotion $promotion = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?float $discountAmount = null;

    #[ORM\ManyToOne(inversedBy: 'deliverOrders')]
    #[Groups(['order:create','order:read','order:write'])]
    private ?User $deliver = null;

    // Delivery tracking fields
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeInterface $acceptedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeInterface $pickedUpAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeInterface $deliveredAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeInterface $estimatedDeliveryTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeInterface $actualDeliveryTime = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    #[Groups(['order:read'])]
    private ?string $qrCode = null; // Unique QR code for customer delivery verification

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeInterface $qrCodeValidatedAt = null; // Timestamp when QR code was validated

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $deliveryFee = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['order:read', 'order:create'])]
    private ?string $deliveryNotes = null;

    #[ORM\OneToOne(mappedBy: 'orderRef', cascade: ['persist', 'remove'])]
    #[Groups(['order:read'])]
    private ?Rating $rating = null;

    #[Groups(['order:create', 'order:write'])]
    private ?string $paymentMethod = null;

    #[Groups(['order:create', 'order:write'])]
    private ?string $promotionCode = null;

    public function __construct()
    {
        $this->status = OrderStatus::STATUS_PENDING;
        $this->priority = PriorityType::PRIORITY_STANDARD;
        $this->createdAt = new \DateTime();
        $this->items = new ArrayCollection();
        // Reference and QR code will be generated by OrderSubscriber if not provided
    }




    public function getId(): ?int
    {
        return $this->id;
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


    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getScheduledDate(): ?\DateTimeInterface
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(?\DateTimeInterface $scheduledDate): static
    {
        $this->scheduledDate = $scheduledDate;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getStoreTotalAmount(): ?float
    {
        return $this->storeTotalAmount;
    }

    public function setStoreTotalAmount(?float $storeTotalAmount): static
    {
        $this->storeTotalAmount = $storeTotalAmount;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus|string $status): self
    {
        if (is_string($status)) {
            try {
                $status = OrderStatus::from($status);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Invalid status: ' . $status);
            }
        }
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPriority(): PriorityType
    {
        return $this->priority;
    }

    public function setPriority(PriorityType|string $priority): self
    {
        if (is_string($priority)) {
            try {
                $priority = PriorityType::from($priority);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Invalid priority: ' . $priority);
            }
        }
        $this->priority = $priority;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }

    public function setPromotion(?Promotion $promotion): static
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?float $discountAmount): static
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }


    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrderParent($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getOrderParent() === $this) {
                $item->setOrderParent(null);
            }
        }

        return $this;
    }

    /**
     * Calculate and set total amount based on order items
     */
    public function calculateTotalAmount(): self
    {
        $total = 0.0;
        $storeTotal = 0.0;
        
        foreach ($this->items as $item) {
            $itemTotal = $item->getTotalPrice();
            if ($itemTotal !== null) {
                $total += $itemTotal;
            }
            
            // Calculate store total based on store prices (if accepted)
            // Store total = quantity * store product price
            if ($item->getStoreStatus() === OrderItemStatus::ACCEPTED) {
                $store = $item->getStore();
                $product = $item->getProduct();
                
                if ($store && $product) {
                    // Try to get price from StoreProduct relation
                    $storeProducts = $product->getStoreProducts();
                    if ($storeProducts) {
                        $storeProduct = $storeProducts->filter(
                            fn($sp) => $sp->getStore() === $store
                        )->first();
                        
                        if ($storeProduct && $storeProduct->getPrice()) {
                            $storeTotal += $storeProduct->getPrice() * $item->getQuantity();
                        }
                    }
                }
            }
        }
        
        $this->totalAmount = $total;
        $this->storeTotalAmount = $storeTotal > 0 ? $storeTotal : null;
        
        return $this;
    }

    /**
     * Auto-calculate total amount before persisting
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function autoCalculateTotalAmount(): void
    {
        $this->calculateTotalAmount();
    }

    public function getDeliver(): ?User
    {
        return $this->deliver;
    }

    public function setDeliver(?User $deliver): static
    {
        $this->deliver = $deliver;

        return $this;
    }

    // Delivery tracking methods
    public function getAcceptedAt(): ?\DateTimeInterface
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeInterface $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;
        return $this;
    }

    public function getPickedUpAt(): ?\DateTimeInterface
    {
        return $this->pickedUpAt;
    }

    public function setPickedUpAt(?\DateTimeInterface $pickedUpAt): static
    {
        $this->pickedUpAt = $pickedUpAt;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeInterface
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeInterface $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function getEstimatedDeliveryTime(): ?\DateTimeInterface
    {
        return $this->estimatedDeliveryTime;
    }

    public function setEstimatedDeliveryTime(?\DateTimeInterface $estimatedDeliveryTime): static
    {
        $this->estimatedDeliveryTime = $estimatedDeliveryTime;
        return $this;
    }

    public function getActualDeliveryTime(): ?\DateTimeInterface
    {
        return $this->actualDeliveryTime;
    }

    public function setActualDeliveryTime(?\DateTimeInterface $actualDeliveryTime): static
    {
        $this->actualDeliveryTime = $actualDeliveryTime;
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

    public function getQrCodeValidatedAt(): ?\DateTimeInterface
    {
        return $this->qrCodeValidatedAt;
    }

    public function setQrCodeValidatedAt(?\DateTimeInterface $qrCodeValidatedAt): static
    {
        $this->qrCodeValidatedAt = $qrCodeValidatedAt;
        return $this;
    }

    public function getDeliveryFee(): ?string
    {
        return $this->deliveryFee;
    }

    public function setDeliveryFee(?string $deliveryFee): static
    {
        $this->deliveryFee = $deliveryFee;
        return $this;
    }

    public function getDeliveryNotes(): ?string
    {
        return $this->deliveryNotes;
    }

    public function setDeliveryNotes(?string $deliveryNotes): static
    {
        $this->deliveryNotes = $deliveryNotes;
        return $this;
    }

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function setRating(?Rating $rating): static
    {
        // set the owning side of the relation if necessary
        if ($rating !== null && $rating->getOrderRef() !== $this) {
            $rating->setOrderRef($this);
        }

        $this->rating = $rating;
        return $this;
    }

    /**
     * Get all unique stores associated with this order through order items
     * 
     * @return Store[]
     */
    public function getStores(): array
    {
        $stores = [];
        foreach ($this->items as $item) {
            $store = $item->getStore();
            if ($store && !in_array($store, $stores, true)) {
                $stores[] = $store;
            }
        }
        return $stores;
    }

    /**
     * Get the primary store (first store) from order items
     * 
     * @return Store|null
     */
    public function getPrimaryStore(): ?Store
    {
        foreach ($this->items as $item) {
            $store = $item->getStore();
            if ($store) {
                return $store;
            }
        }
        return null;
    }

    /**
     * Check if the order belongs to a specific store
     * 
     * @param Store $store
     * @return bool
     */
    public function belongsToStore(Store $store): bool
    {
        foreach ($this->items as $item) {
            if ($item->getStore() === $store) {
                return true;
            }
        }
        return false;
    }
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPromotionCode(): ?string
    {
        return $this->promotionCode;
    }

    public function setPromotionCode(?string $promotionCode): self
    {
        $this->promotionCode = $promotionCode;
        return $this;
    }
}

