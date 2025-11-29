<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\ProductPromotionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductPromotionRepository::class)]
#[ORM\Table(name: '`product_promotion`')]
#[ORM\HasLifecycleCallbacks]
class ProductPromotion
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\ManyToOne(inversedBy: 'productPromotions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['product-promotion:read'])]
    private ?Product $product = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product-promotion:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product-promotion:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(100)]
    #[Groups(['product-promotion:read'])]
    private ?float $discountPercentage = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['product-promotion:read'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'startDate')]
    #[Groups(['product-promotion:read'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['product-promotion:read'])]
    private bool $isActive = true;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isActive = true;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
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

    public function getDiscountPercentage(): ?float
    {
        return $this->discountPercentage;
    }

    public function setDiscountPercentage(float $discountPercentage): static
    {
        $this->discountPercentage = $discountPercentage;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Check if promotion is valid (active, within date range)
     */
    public function isValid(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = new \DateTime();

        if ($this->startDate && $now < $this->startDate) {
            return false;
        }

        if ($this->endDate && $now > $this->endDate) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discounted price for a product
     */
    public function calculateDiscountedPrice(float $originalPrice): float
    {
        if (!$this->isValid()) {
            return $originalPrice;
        }

        $discount = ($originalPrice * $this->discountPercentage) / 100;
        return max(0, $originalPrice - $discount);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s%% (%s)',
            $this->product?->getName() ?? 'Product',
            $this->discountPercentage ?? 0,
            $this->name ?? 'Promotion'
        );
    }
}

