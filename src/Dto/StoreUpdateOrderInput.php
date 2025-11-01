<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class StoreUpdateOrderInput
{
    #[Assert\NotNull(message: 'Order item actions are required')]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, minMessage: 'At least one order item action is required')]
    #[Assert\Valid]
    public ?array $orderItemActions = null;
}

