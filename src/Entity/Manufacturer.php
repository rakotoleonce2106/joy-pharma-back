<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\ManufacturerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

#[ORM\Entity(repositoryClass: ManufacturerRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'ipartial'])]
class Manufacturer
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\Column(length: 255)]
    #[Groups(['manufacturer:read', 'manufacturer:write', 'product:read'])]
    #[Assert\NotBlank(groups: ['create'])]
    private ?string $name = null;

    #[ORM\Column(length: 1000, nullable: true)]
    #[Groups(['manufacturer:read', 'manufacturer:write', 'product:read'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: MediaObject::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['manufacturer:read', 'manufacturer:write', 'product:read'])]
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

    public function setImageFile(?UploadedFile $file): static
    {
        if ($file) {
            // Si une image existe déjà, mettre à jour le fichier de l'existant
            if ($this->image) {
                $this->image->setFile($file);
            } else {
                // Sinon, créer un nouveau MediaObject
                $mediaObject = new MediaObject();
                $mediaObject->setFile($file);
                $this->image = $mediaObject;
            }
        }

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
