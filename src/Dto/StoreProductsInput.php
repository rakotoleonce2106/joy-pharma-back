<?php

// src/Dto/OrderInput.php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class StoreProductsInput
{

    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    public array $items = [];
}
