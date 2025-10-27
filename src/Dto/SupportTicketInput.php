<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SupportTicketInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $subject;

    #[Assert\NotBlank]
    #[Assert\Length(min: 10)]
    public string $message;

    #[Assert\Choice(choices: ['low', 'normal', 'high', 'urgent'])]
    public string $priority = 'normal';
}


