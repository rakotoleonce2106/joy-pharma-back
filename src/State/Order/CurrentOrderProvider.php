<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\SecurityBundle\Security;

class CurrentOrderProvider implements ProviderInterface
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
            return ['success' => true, 'data' => null, 'message' => 'No active order'];
        }

        $order = $this->orderRepository->findCurrentOrderForDeliveryPerson($user);

        return $order ?? ['success' => true, 'data' => null, 'message' => 'No active order'];
    }
}


