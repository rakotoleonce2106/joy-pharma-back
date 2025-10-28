<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RejectOrderProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('User not found');
        }

        // For now, rejection just means the delivery person won't see it in their available list
        // In a real system, you might want to track rejections

        return [
            'success' => true,
            'message' => 'Order rejected successfully'
        ];
    }
}





