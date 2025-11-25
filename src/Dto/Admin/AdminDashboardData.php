<?php

namespace App\Dto\Admin;

class AdminDashboardData
{
    public function __construct(
        public array $counters,
        public array $financials,
        public array $map,
        public array $lists
    ) {
    }
}

