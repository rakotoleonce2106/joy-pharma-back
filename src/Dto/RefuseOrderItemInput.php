<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RefuseOrderItemInput
{
    #[Assert\NotBlank(message: 'Order item ID is required')]
    public ?int $orderItemId = null;

    #[Assert\NotBlank(message: 'Reason is required for refusing an order item')]
    public ?string $reason = null;
}

