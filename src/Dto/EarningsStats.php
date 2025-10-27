<?php

namespace App\Dto;

class EarningsStats
{
    public function __construct(
        public string $period,
        public string $totalEarnings,
        public int $totalDeliveries,
        public string $averagePerDelivery,
        public array $earnings
    ) {
    }
}


