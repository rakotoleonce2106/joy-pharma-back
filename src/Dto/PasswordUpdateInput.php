<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PasswordUpdateInput
{
    #[Assert\NotBlank(message: "Current password is required")]
    public string $currentPassword;

    #[Assert\NotBlank(message: "New password is required")]
    #[Assert\Length(min: 8, minMessage: "Password must be at least 8 characters long")]
    public string $newPassword;
}