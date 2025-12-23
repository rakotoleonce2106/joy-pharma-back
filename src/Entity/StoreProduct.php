<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityStatusTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\StoreProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StoreProductRepository::class)]
class StoreProduct
{
    use EntityIdTrait;
    use EntityStatusTrait;
    use EntityTimestampTrait;

    #[Assert\NotBlank(groups: ['create'])]
    #[ORM\ManyToOne(inversedBy: 'storeProducts')]
    #[Groups(['store-product:read', 'store-product:write'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'storeProducts')]
    #[Groups(['store-product:read', 'store-product:write'])]
    private ?Store $store = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['store-product:read', 'store-product:write'])]
    private ?float $unitPrice = null;

    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\GreaterThanOrEqual(0)]
    #[ORM\Column]
    #[Groups(['store-product:read', 'store-product:write'])]
    private ?int $stock = null;

    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\GreaterThan(0)]
    #[ORM\Column]
    #[Groups(['store-product:read', 'store-product:write'])]
    private ?float $price = null;


    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

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

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?float $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }


    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }
}
