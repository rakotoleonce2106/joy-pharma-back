<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class StoreQRScanInput
{
    #[Assert\NotBlank(message: 'QR code is required')]
    public string $qrCode;
}

