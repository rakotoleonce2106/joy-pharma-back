<?php

namespace App\Dto\Admin;

use App\Entity\MediaObject;
use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraints as Assert;

class ManufacturerInput
{
    #[Assert\NotBlank]
    public ?string $name = null;

    public ?string $description = null;

    /**
     * Reference to uploaded MediaObject for logo
     * API Platform will automatically deserialize IRI (e.g., "/api/media_objects/123") to MediaObject entity
     * Use POST /api/media_objects to upload file first, then use the returned IRI here
     * Old image will be automatically deleted if replaced
     */
    #[ApiProperty(types: ['https://schema.org/image'], iris: [MediaObject::class])]
    public ?MediaObject $image = null;
}

