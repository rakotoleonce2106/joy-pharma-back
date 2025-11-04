<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ValidateQRInput
{
    #[Assert\NotBlank(message: 'QR Code is required')]
    public string $qrCode;

    #[Assert\Type(type: 'float', message: 'Latitude must be a number')]
    public ?float $latitude = null;

    #[Assert\Type(type: 'float', message: 'Longitude must be a number')]
    public ?float $longitude = null;
}

