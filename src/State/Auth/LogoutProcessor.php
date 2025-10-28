<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\LogoutResponse;

class LogoutProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // JWT tokens are stateless, so we just return success
        // The client should remove the token from storage
        return new LogoutResponse();
    }
}





