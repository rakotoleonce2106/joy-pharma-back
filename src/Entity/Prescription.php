<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityStatusTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\PrescriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PrescriptionRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['prescription:read', 'id:read', 'user:read', 'media_object:read', 'product:read']],
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['prescription:read', 'id:read', 'user:read', 'media_object:read', 'product:read']],
        ),
        new Post(
            denormalizationContext: ['groups' => ['prescription:write']],
            normalizationContext: ['groups' => ['prescription:read', 'id:read', 'user:read', 'media_object:read', 'product:read']],
        ),
        new Put(
            denormalizationContext: ['groups' => ['prescription:write']],
            normalizationContext: ['groups' => ['prescription:read', 'id:read', 'user:read', 'media_object:read', 'product:read']],
        ),
        new Patch(
            denormalizationContext: ['groups' => ['prescription:write']],
            normalizationContext: ['groups' => ['prescription:read', 'id:read', 'user:read', 'media_object:read', 'product:read']],
        ),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['prescription:read', 'id:read']],
    denormalizationContext: ['groups' => ['prescription:write']],
)]
class Prescription
{
    use EntityIdTrait;
    use EntityStatusTrait;
    use EntityTimestampTrait;

    #[ORM\Column(length: 255)]
    #[Groups(['prescription:read', 'prescription:write'])]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['prescription:read', 'prescription:write'])]
    private ?string $notes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['prescription:read', 'prescription:write'])]
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: MediaObject::class, cascade: ['persist'])]
    #[Groups(['prescription:read', 'prescription:write'])]
    #[ApiProperty(types: ['https://schema.org/image'])]
    private ?MediaObject $prescriptionFile = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'prescription_products')]
    #[Groups(['prescription:read', 'prescription:write'])]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPrescriptionFile(): ?MediaObject
    {
        return $this->prescriptionFile;
    }

    public function setPrescriptionFile(?MediaObject $prescriptionFile): static
    {
        $this->prescriptionFile = $prescriptionFile;

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
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        $this->products->removeElement($product);

        return $this;
    }
}