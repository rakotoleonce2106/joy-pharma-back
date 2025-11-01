<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class StoreOrderItemActionInput
{
    #[Assert\NotBlank(message: 'Order item ID is required')]
    public ?int $orderItemId = null;

    #[Assert\NotBlank(message: 'Action is required (accept, refuse, or suggest)')]
    #[Assert\Choice(choices: ['accept', 'refuse', 'suggest'], message: 'Action must be one of: accept, refuse, suggest')]
    public ?string $action = null;

    // For accept action
    public ?string $notes = null;

    // For refuse action
    public ?string $reason = null;

    // For suggest action
    public ?int $suggestedProductId = null;
    public ?string $suggestion = null;
}

