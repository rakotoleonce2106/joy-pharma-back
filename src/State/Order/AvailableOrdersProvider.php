<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Order;
use App\Repository\OrderRepository;

/**
 * Provides all available orders (pending and not assigned to a deliver)
 */
class AvailableOrdersProvider implements ProviderInterface
{
    public function __construct(private readonly OrderRepository $orderRepository)
    {
    }

    /**
     * @param class-string<Order>|Order $operationName
     * @return array<Order>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->orderRepository->findAllAvailableOrders();
    }
}


