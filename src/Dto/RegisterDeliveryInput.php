<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterDeliveryInput
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
    #[Assert\Choice(choices: ['bike', 'motorcycle', 'car', 'van'])]
    public string $vehicleType;

    public ?string $vehiclePlate = null;

    /**
     * Residence proof document (IRI)
     */
    #[Assert\NotBlank]
    public ?string $residenceDocument = null;

    /**
     * Vehicle paper document (IRI)
     */
    #[Assert\NotBlank]
    public ?string $vehicleDocument = null;
}

