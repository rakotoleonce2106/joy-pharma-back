<?php

// src/Dto/OrderInput.php

namespace App\Dto;

use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class OrderInput
{
    //lat
    #[Assert\NotBlank]
    public ?string $latitude;

    //lng
    #[Assert\NotBlank]
    public ?string $longitude;

    #[Assert\NotBlank]
    public ?string $address;

    //date
    #[Assert\NotBlank]
    public ?DateTimeInterface $date;

    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    /**
     * @var array<ItemInput>
     */
    public array $items = [];
    
    #[Assert\NotBlank]
    public ?string $phone = null;

    #[Assert\NotBlank]
    public ?string $priority = null;

    #[Assert\NotBlank]
    public ?string $notes = null;

    //payment method
    #[Assert\NotBlank]
    public ?string $paymentMethod;
}
