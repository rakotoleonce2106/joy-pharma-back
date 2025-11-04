<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\OrderRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CurrentOrderProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new \LogicException('User not authenticated or not a valid User entity.');
        }

        // Check if user has ROLE_DELIVER role
        if (!in_array('ROLE_DELIVER', $user->getRoles(), true)) {
            // User is not a delivery person, return null order
            throw new \LogicException('User is not a delivery person.');
        }

        // Get the order where this delivery person is assigned
        $order = $this->orderRepository->findCurrentOrderForDeliveryPerson($user);

        // If no current order found, throw a proper exception with a clear message
        if (!$order) {
            return [];
        }

        return [$order];
    }
}
