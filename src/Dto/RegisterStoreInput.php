<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterStoreInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password;

    #[Assert\NotBlank]
    public string $firstName;

    #[Assert\NotBlank]
    public string $lastName;

    #[Assert\NotBlank]
    public string $phone;

    #[Assert\NotBlank]
    public string $storeName;

    #[Assert\NotBlank]
    public string $storeAddress;

    public ?string $storePhone = null;
    
    public ?string $storeEmail = null;
    
    public ?string $storeDescription = null;
    
    public ?string $storeCity = null;
    
    public ?float $storeLatitude = null;
    
    public ?float $storeLongitude = null;
}

