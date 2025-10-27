<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\OrderRepository;

class AvailableOrdersProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $page = max(1, (int) ($context['filters']['page'] ?? 1));
        $limit = min(50, max(1, (int) ($context['filters']['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        return $this->orderRepository->findAvailableOrders($limit, $offset);
    }
}


