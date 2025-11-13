<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class OrderItemInput
{
    #[Assert\NotBlank]
    public ?int $product = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?int $quantity = null;

    public ?int $store = null;
}

