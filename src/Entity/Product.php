<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityStatusTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    use EntityIdTrait;
    use EntityStatusTrait;
    use EntityTimestampTrait;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $code = null;


    #[ORM\Column(length: 2000, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $description = null;

    /**
     * @var Collection<int, MediaFile>
     */
    #[ORM\OneToMany(targetEntity: MediaFile::class, mappedBy: 'product')]
    #[Groups(['product:read'])]
    private Collection $images;


    #[ORM\ManyToOne]
    #[Groups(['product:read'])]
    private ?Form $form = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[Groups(['product:read'])]
    private ?Brand $brand = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[Groups(['product:read'])]
    private ?Manufacturer $manufacturer = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(['product:read'])]
    private ?Price $price = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products')]
    #[Groups(['product:read'])]
    private Collection $category;

    /**
     * @var Collection<int, Restricted>
     */
    #[ORM\ManyToMany(targetEntity: Restricted::class, inversedBy: 'products')]
    #[Groups(['product:read'])]
    private Collection $restricted;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->category = new ArrayCollection();
        $this->restricted = new ArrayCollection();
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

    /**
     * @return Collection<int, MediaFile>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(MediaFile $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(MediaFile $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function setForm(?Form $form): static
    {
        $this->form = $form;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getManufacturer(): ?Manufacturer
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?Manufacturer $manufacturer): static
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategory(): Collection
    {
        return $this->category;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->category->contains($category)) {
            $this->category->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->category->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, Restricted>
     */
    public function getRestricted(): Collection
    {
        return $this->restricted;
    }

    public function addRestricted(Restricted $restricted): static
    {
        if (!$this->restricted->contains($restricted)) {
            $this->restricted->add($restricted);
        }

        return $this;
    }

    public function removeRestricted(Restricted $restricted): static
    {
        $this->restricted->removeElement($restricted);

        return $this;
    }
}
