<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\ManufacturerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ManufacturerRepository::class)]
class Manufacturer
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\Column(length: 255)]
    #[Groups(['manufacturer:read','product:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 1000, nullable: true)]
    #[Groups(['manufacturer:read','product:read'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['manufacturer:read','product:read'])]
    #[ApiProperty(types: ['https://schema.org/image'])]
    private ?MediaObject $image = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'manufacturer')]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
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

    public function getImage(): ?MediaObject
    {
        return $this->image;
    }

    public function setImage(?MediaObject $image): static
    {
        $this->image = $image;

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
            $product->setManufacturer($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getManufacturer() === $this) {
                $product->setManufacturer(null);
            }
        }

        return $this;
    }
}
