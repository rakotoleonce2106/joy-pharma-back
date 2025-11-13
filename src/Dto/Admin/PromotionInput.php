<?php

namespace App\Dto\Admin;

use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PromotionInput
{
    #[Assert\NotBlank]
    public ?string $code = null;

    #[Assert\NotBlank]
    public ?string $type = null; // 'percentage' or 'fixed'

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?float $value = null;

    #[Assert\NotBlank]
    public ?DateTimeInterface $startDate = null;

    #[Assert\NotBlank]
    public ?DateTimeInterface $endDate = null;

    public ?string $description = null;

    public ?float $minimumOrderAmount = null;
}

