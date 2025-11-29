<?php

namespace App\Dto\Admin;

use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PromotionInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    public ?string $code = null;

    #[Assert\NotBlank]
    public ?string $name = null;

    public ?string $description = null;

    #[Assert\NotBlank]
    public ?string $discountType = null; // 'percentage' or 'fixed_amount'

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?float $discountValue = null;

    #[Assert\PositiveOrZero]
    public ?float $minimumOrderAmount = null;

    #[Assert\PositiveOrZero]
    public ?float $maximumDiscountAmount = null;

    public ?DateTimeInterface $startDate = null;

    public ?DateTimeInterface $endDate = null;

    #[Assert\PositiveOrZero]
    public ?int $usageLimit = null;

    public ?bool $isActive = true;
}

