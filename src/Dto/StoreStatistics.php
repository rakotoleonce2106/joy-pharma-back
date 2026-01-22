<?php

namespace App\Dto;

class StoreStatistics
{
    public function __construct(
        public int $pendingCount,
        public array $recentOrders,
        public int $recentOrdersCount,
        public array $statistics
    ) {
    }
}

