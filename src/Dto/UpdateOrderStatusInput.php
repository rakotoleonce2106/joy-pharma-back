<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateOrderStatusInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])]
    public string $status;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?string $notes = null;
}


