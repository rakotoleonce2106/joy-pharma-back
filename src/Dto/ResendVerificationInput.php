<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ResendVerificationInput
{
    #[Assert\NotBlank(message: 'L\'adresse email est requise')]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide')]
    public string $email;
}