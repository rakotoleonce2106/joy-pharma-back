<?php

namespace App\Dto;

class AvailabilityResponse
{
    public function __construct(
        public bool $isOnline,
        public ?string $currentLatitude,
        public ?string $currentLongitude,
        public ?\DateTimeInterface $lastLocationUpdate,
        public int $totalDeliveries,
        public ?float $averageRating,
        public string $totalEarnings
    ) {
    }
}

