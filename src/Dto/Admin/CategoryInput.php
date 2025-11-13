<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryInput
{
    #[Assert\NotBlank]
    public ?string $name = null;

    public ?string $description = null;

    public ?int $parent = null;

    public ?string $color = null;
}

