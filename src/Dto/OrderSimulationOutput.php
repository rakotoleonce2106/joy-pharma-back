<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class OrderSimulationOutput
{
    #[Groups(['simulation:read'])]
    public float $subtotal = 0.0;

    #[Groups(['simulation:read'])]
    public float $discountAmount = 0.0;

    #[Groups(['simulation:read'])]
    public float $totalAmount = 0.0;

    #[Groups(['simulation:read'])]
    public ?string $promotionCode = null;

    #[Groups(['simulation:read'])]
    public ?array $promotion = null;

    #[Groups(['simulation:read'])]
    public array $items = [];

    #[Groups(['simulation:read'])]
    public bool $promotionValid = false;

    #[Groups(['simulation:read'])]
    public ?string $promotionError = null;
}

