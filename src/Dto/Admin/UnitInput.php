<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class UnitInput
{
    #[Assert\NotBlank]
    public ?string $name = null;
}

