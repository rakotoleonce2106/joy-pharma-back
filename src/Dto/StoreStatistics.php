<?php

namespace App\Dto;

class StoreStatistics
{
    public function __construct(
        public int $pendingOrdersCount,
        public int $todayOrdersCount,
        public int $lowStockCount,
        public float $todayEarnings,
        public float $weeklyEarnings,
        public float $monthlyEarnings
    ) {
    }
}

