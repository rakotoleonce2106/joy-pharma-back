<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class ProductInput
{
    #[Assert\NotBlank]
    public ?string $name = null;

    #[Assert\NotBlank]
    public ?string $code = null;

    public ?string $description = null;

    /**
     * @var array<int>
     */
    public array $categories = [];

    public ?int $form = null;

    public ?int $brand = null;

    public ?int $manufacturer = null;

    public ?int $unit = null;

    public ?float $unitPrice = null;

    public ?float $totalPrice = null;

    public ?float $quantity = null;

    public ?int $stock = null;

    public ?string $currency = null;

    public bool $isActive = true;

    /**
     * @var array<mixed>
     */
    public array $variants = [];
}

