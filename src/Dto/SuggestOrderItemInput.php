<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SuggestOrderItemInput
{
    #[Assert\NotBlank(message: 'Order item ID is required')]
    public ?int $orderItemId = null;

    #[Assert\NotBlank(message: 'Suggested product ID is required')]
    public ?int $suggestedProductId = null;

    public ?string $suggestion = null;

    public ?string $notes = null;
}

