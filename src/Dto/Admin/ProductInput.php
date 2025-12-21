<?php

namespace App\Dto\Admin;

use App\Entity\MediaObject;
use ApiPlatform\Metadata\ApiProperty;
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

    /**
     * Array of MediaObject references for product images
     * API Platform will automatically deserialize IRI array (e.g., ["/api/media_objects/1", "/api/media_objects/2"]) to MediaObject entities
     * Use POST /api/media_objects to upload files first, then use the returned IRIs here
     * Old images not in this array will be automatically deleted
     */
    #[ApiProperty(types: ['https://schema.org/image'], iris: [MediaObject::class])]
    public array $images = [];
}

