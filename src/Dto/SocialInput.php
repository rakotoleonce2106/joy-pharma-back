<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SocialInput
{
    #[Assert\Length(max: 255)]
    public ?string $firstName;

    #[Assert\Length(max: 255)]
    public ?string $lastName;

    #[Assert\Length(max: 255)]
    public ?string $email;

    #[Assert\Length(max: 255)]
    public string $socialId;

    #[Assert\Length(max: 255)]
    public ?string $imageUrl;

}
