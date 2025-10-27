<?php

namespace App\Dto;

class LogoutResponse
{
    public function __construct(
        public bool $success = true,
        public string $message = 'Successfully logged out'
    ) {
    }
}


