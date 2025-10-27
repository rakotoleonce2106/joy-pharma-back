<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OrderItem
{

    use EntityIdTrait;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:create','order:read'])]
    private ?Product $product = null;

    #[ORM\Column]
    #[Groups(['order:create','order:read'])]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Groups(['order:create','order:read'])]
    private ?float $totalPrice = 0.0;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?Order $orderParent = null;

    #[ORM\ManyToOne]
    private ?Store $store = null;

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

}
