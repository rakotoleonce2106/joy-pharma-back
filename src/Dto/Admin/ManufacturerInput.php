<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class ManufacturerInput
{
    #[Assert\NotBlank]
    public ?string $name = null;

    public ?string $description = null;
}

