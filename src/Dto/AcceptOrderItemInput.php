<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class AcceptOrderItemInput
{
    #[Assert\NotBlank(message: 'Order item ID is required')]
    public ?int $orderItemId = null;

    public ?string $notes = null;
}

