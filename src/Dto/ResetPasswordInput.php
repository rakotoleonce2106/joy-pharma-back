<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;




class ResetPasswordInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['send_email:write','check_code:write','reset_password:write'])]
    public ?string $email = null;

    #[Assert\Length(min: 4, max: 6)]
    #[Groups(['check_code:write'])]
    public ?string $code = null;

    #[Assert\Length(min: 8, max: 4096)]
    #[Groups(['reset_password:write'])]
    public ?string $password = null;
}