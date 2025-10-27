<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\SecurityBundle\Security;

class OrderHistoryProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return [];
        }

        $page = max(1, (int) ($context['filters']['page'] ?? 1));
        $limit = min(50, max(1, (int) ($context['filters']['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = $context['filters']['status'] ?? null;

        return $this->orderRepository->findOrderHistoryForDeliveryPerson(
            $user,
            $limit,
            $offset,
            $status
        );
    }
}


