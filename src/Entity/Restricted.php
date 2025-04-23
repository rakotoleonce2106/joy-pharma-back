<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\RestrictedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestrictedRepository::class)]
class Restricted
{
    use EntityIdTrait;

    #[ORM\Column(length: 255)]
    private ?string $waitingFor = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'restricted')]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }


    public function getWaitingFor(): ?string
    {
        return $this->waitingFor;
    }

    public function setWaitingFor(string $waitingFor): static
    {
        $this->waitingFor = $waitingFor;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->addRestricted($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            $product->removeRestricted($this);
        }

        return $this;
    }
}
