<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class UpdatePasswordInput
{
    #[Assert\NotBlank(message: 'Current password is required')]
    #[Groups(['update_password:write'])]
    public ?string $currentPassword = null;

    #[Assert\NotBlank(message: 'New password is required')]
    #[Assert\Length(
        min: 8,
        max: 4096,
        minMessage: 'Password must be at least {{ limit }} characters long',
        maxMessage: 'Password cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Password must contain at least one uppercase letter, one lowercase letter, and one number'
    )]
    #[Groups(['update_password:write'])]
    public ?string $newPassword = null;

    #[Assert\NotBlank(message: 'Password confirmation is required')]
    #[Assert\EqualTo(
        propertyPath: 'newPassword',
        message: 'Password confirmation must match the new password'
    )]
    #[Groups(['update_password:write'])]
    public ?string $confirmPassword = null;
}

