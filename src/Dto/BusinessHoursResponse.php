<?php

namespace App\Dto;

class BusinessHoursResponse
{
    public function __construct(
        public int $storeId,
        public string $storeName,
        public array $hours,
        public bool $isCurrentlyOpen,
        public ?string $nextOpenTime = null
    ) {
    }
}

