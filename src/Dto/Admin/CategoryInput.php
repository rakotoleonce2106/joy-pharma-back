<?php

namespace App\Dto\Admin;

use App\Entity\MediaObject;
use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryInput
{
    /**
     * Category name - required for create, optional for update
     */
    #[Assert\NotBlank(groups: ['create'])]
    public ?string $name = null;

    public ?string $description = null;

    /**
     * Parent category ID (can be integer, null, empty string, or "null" string)
     */
    public int|string|null $parent = null;

    public ?string $color = null;

    /**
     * Reference to uploaded MediaObject for image
     * API Platform will automatically deserialize IRI (e.g., "/api/media_objects/123") to MediaObject entity
     * Use POST /api/media_objects to upload file first, then use the returned IRI here
     */
    #[ApiProperty(types: ['https://schema.org/image'], iris: [MediaObject::class])]
    public ?MediaObject $image = null;

    /**
     * Reference to uploaded MediaObject for icon
     * API Platform will automatically deserialize IRI (e.g., "/api/media_objects/123") to MediaObject entity
     * Use POST /api/media_objects to upload file first, then use the returned IRI here
     */
    #[ApiProperty(types: ['https://schema.org/image'], iris: [MediaObject::class])]
    public ?MediaObject $icon = null;
}

