<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

enum OrderItemStatus: string
{
    case PENDING = 'pending';           // Waiting for store action
    case ACCEPTED = 'accepted';         // Store accepted the item
    case REFUSED = 'refused';           // Store refused the item
    case SUGGESTED = 'suggested';       // Store suggested alternative (needs admin approval)
    case APPROVED = 'approved';         // Admin approved the suggestion
    case REJECTED = 'rejected';         // Admin rejected the suggestion
    case RECUPERATED = 'recuperated';   // Recuperée - Item collected by delivery person
}

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OrderItem
{

    use EntityIdTrait;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:create','order:read','order_item:write'])]
    private ?Product $product = null;

    #[ORM\Column]
    #[Groups(['order:create','order:read','order_item:write'])]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Groups(['order:create','order:read'])]
    private ?float $totalPrice = 0.0;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?Order $orderParent = null;

    #[ORM\ManyToOne]
    #[Groups(['order:read','order_item:write'])]
    private ?Store $store = null;

    #[ORM\Column(type: 'string', length: 20, enumType: OrderItemStatus::class)]
    #[Groups(['order:read'])]
    private OrderItemStatus $storeStatus;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $storeNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $storeSuggestion = null;

    #[ORM\ManyToOne]
    #[Groups(['order:read'])]
    private ?Product $suggestedProduct = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?float $storePrice = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeInterface $storeActionAt = null;

    public function __construct()
    {
        $this->storeStatus = OrderItemStatus::PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getOrderParent(): ?Order
    {
        return $this->orderParent;
    }

    public function setOrderParent(?Order $orderParent): static
    {
        $this->orderParent = $orderParent;

        return $this;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): static
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Calculate total price based on product price and quantity
     */
    public function calculateTotalPrice(): self
    {
        // Reset to 0 if no product or quantity
        if (!$this->product || !$this->quantity || $this->quantity <= 0) {
            $this->totalPrice = 0.0;
            return $this;
        }
        
        // Try to get price from StoreProduct if store is specified
        if ($this->store) {
            try {
                $storeProducts = $this->product->getStoreProducts();
                if ($storeProducts) {
                    $storeProduct = $storeProducts->filter(
                        fn($sp) => $sp->getStore() === $this->store
                    )->first();
                    
                    if ($storeProduct && $storeProduct->getPrice() > 0) {
                        $this->totalPrice = $storeProduct->getPrice() * $this->quantity;
                        return $this;
                    }
                }
            } catch (\Exception $e) {
                // If storeProducts not loaded, continue to base price
            }
        }
        
        // Otherwise use product base price (use totalPrice if available, else unitPrice)
        $productPrice = $this->product->getTotalPrice() ?? $this->product->getUnitPrice() ?? 0;
        $this->totalPrice = $productPrice * $this->quantity;

        return $this;
    }

    /**
     * Auto-calculate total price before persisting
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function autoCalculateTotalPrice(): void
    {
        $this->calculateTotalPrice();
    }

    /**
     * String representation for debugging
     */
    public function __toString(): string
    {
        return sprintf(
            'OrderItem #%d: %s x %d = %s Ar',
            $this->id ?? 0,
            $this->product?->getName() ?? 'No product',
            $this->quantity ?? 0,
            number_format($this->totalPrice ?? 0, 2)
        );
    }

    public function getStoreStatus(): OrderItemStatus
    {
        return $this->storeStatus;
    }

    public function setStoreStatus(OrderItemStatus $storeStatus): static
    {
        $this->storeStatus = $storeStatus;
        $this->storeActionAt = new \DateTime();
        return $this;
    }

    public function getStoreNotes(): ?string
    {
        return $this->storeNotes;
    }

    public function setStoreNotes(?string $storeNotes): static
    {
        $this->storeNotes = $storeNotes;
        return $this;
    }

    public function getStoreSuggestion(): ?string
    {
        return $this->storeSuggestion;
    }

    public function setStoreSuggestion(?string $storeSuggestion): static
    {
        $this->storeSuggestion = $storeSuggestion;
        return $this;
    }

    public function getStorePrice(): ?float
    {
        return $this->storePrice;
    }

    public function setStorePrice(?float $storePrice): static
    {
        $this->storePrice = $storePrice;
        return $this;
    }

    public function getStoreActionAt(): ?\DateTimeInterface
    {
        return $this->storeActionAt;
    }

    public function setStoreActionAt(?\DateTimeInterface $storeActionAt): static
    {
        $this->storeActionAt = $storeActionAt;
        return $this;
    }

    public function getSuggestedProduct(): ?Product
    {
        return $this->suggestedProduct;
    }

    public function setSuggestedProduct(?Product $suggestedProduct): static
    {
        $this->suggestedProduct = $suggestedProduct;
        return $this;
    }

}
