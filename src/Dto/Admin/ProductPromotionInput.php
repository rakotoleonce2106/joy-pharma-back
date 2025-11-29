<?php

namespace App\Dto\Admin;

use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductPromotionInput
{
    #[Assert\NotBlank]
    public ?int $productId = null;

    public ?string $name = null;

    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(100)]
    public ?float $discountPercentage = null;

    public ?DateTimeInterface $startDate = null;

    public ?DateTimeInterface $endDate = null;

    public ?bool $isActive = true;
}

