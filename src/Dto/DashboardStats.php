<?php

namespace App\Dto;

class DashboardStats
{
    public function __construct(
        public string $period,
        public int $totalDeliveries,
        public string $totalEarnings,
        public ?float $averageRating,
        public bool $isOnline,
        public bool $hasActiveOrder,
        public array $lifetimeStats
    ) {
    }
}





