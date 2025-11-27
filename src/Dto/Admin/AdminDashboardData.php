<?php

namespace App\Dto\Admin;

use Symfony\Component\Serializer\Annotation\Groups;

class AdminDashboardData
{
    public function __construct(
        #[Groups(['admin:dashboard:read'])]
        public array $counters,
        
        #[Groups(['admin:dashboard:read'])]
        public array $financials,
        
        #[Groups(['admin:dashboard:read'])]
        public array $map,
        
        #[Groups(['admin:dashboard:read'])]
        public array $lists
    ) {
    }
}

