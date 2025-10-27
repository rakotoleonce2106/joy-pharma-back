<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SOSInput
{
    #[Assert\NotBlank]
    #[Assert\Range(min: -90, max: 90)]
    public float $latitude;

    #[Assert\NotBlank]
    #[Assert\Range(min: -180, max: 180)]
    public float $longitude;

    public ?string $notes = null;

    public ?int $orderId = null;
}


