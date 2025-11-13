<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class FormInput
{
    #[Assert\NotBlank]
    public ?string $name = null;
}

