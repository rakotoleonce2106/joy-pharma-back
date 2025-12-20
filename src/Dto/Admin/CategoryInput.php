<?php

namespace App\Dto\Admin;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryInput
{
    #[Assert\NotBlank]
    public ?string $name = null;

    public ?string $description = null;

    /**
     * Parent category ID (can be integer, null, empty string, or "null" string)
     */
    public int|string|null $parent = null;

    public ?string $color = null;

    /**
     * Image file for the category (multipart/form-data)
     */
    #[Ignore]
    public ?UploadedFile $image = null;

    /**
     * Icon/SVG file for the category (multipart/form-data)
     */
    #[Ignore]
    public ?UploadedFile $icon = null;
}

