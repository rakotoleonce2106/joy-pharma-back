<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\PriceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PriceRepository::class)]
class Price
{
    use EntityIdTrait;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:read'])]
    private ?Quantity $quantity = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:read'])]
    private ?float $unitPrice = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:read'])]
    private ?float $totalPrice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?Quantity
    {
        return $this->quantity;
    }

    public function setQuantity(Quantity $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }
}
