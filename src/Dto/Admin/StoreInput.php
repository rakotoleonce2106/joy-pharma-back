<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class StoreInput
{
    #[Assert\NotBlank]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $ownerEmail = null;

    public ?string $description = null;

    public ?string $address = null;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?string $phone = null;

    public ?string $email = null;
}

