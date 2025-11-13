<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class UserInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    public ?string $firstName = null;

    #[Assert\NotBlank]
    public ?string $lastName = null;

    public ?string $password = null;

    /**
     * @var array<string>
     */
    public array $roles = [];

    public bool $active = true;
}

