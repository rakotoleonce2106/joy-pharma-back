<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyEmailInput
{
    #[Assert\NotBlank(message: 'L\'adresse email est requise')]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide')]
    public string $email;

    #[Assert\NotBlank(message: 'Le code de vérification est requis')]
    #[Assert\Length(
        min: 6,
        max: 6,
        exactMessage: 'Le code de vérification doit contenir exactement 6 chiffres'
    )]
    #[Assert\Regex(
        pattern: '/^\d{6}$/',
        message: 'Le code de vérification doit contenir uniquement des chiffres'
    )]
    public string $code;
}