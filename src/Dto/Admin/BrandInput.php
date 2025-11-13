<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class BrandInput
{
    #[Assert\NotBlank]
    public ?string $name = null;
}

