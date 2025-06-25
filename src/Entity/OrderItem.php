<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{

    use EntityIdTrait;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[Groups(['order:create','order:read'])]
    private ?Product $product = null;

    #[ORM\Column]
    #[Groups(['order:create','order:read'])]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?float $totalPrice = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?Order $items = null;

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

    public function getItems(): ?Order
    {
        return $this->items;
    }

    public function setItems(?Order $items): static
    {
        $this->items = $items;

        return $this;
    }
}
