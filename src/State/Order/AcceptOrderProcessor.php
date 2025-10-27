<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\OrderStatus;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AcceptOrderProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('User not found');
        }

        $orderId = $uriVariables['id'] ?? null;
        if (!$orderId) {
            throw new NotFoundHttpException('Order ID not provided');
        }

        // Check if delivery person already has an active order
        $currentOrder = $this->orderRepository->findCurrentOrderForDeliveryPerson($user);
        if ($currentOrder) {
            throw new ConflictHttpException('You already have an active order');
        }

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new NotFoundHttpException('Order not found');
        }

        // Check if order is already assigned
        if ($order->getDeliver()) {
            throw new ConflictHttpException('Order already assigned to another delivery person');
        }

        // Check if order is in correct status
        if ($order->getStatus() !== OrderStatus::STATUS_PENDING) {
            throw new ConflictHttpException('Order is not available for assignment');
        }

        // Assign order to delivery person
        $order->setDeliver($user);
        $order->setStatus(OrderStatus::STATUS_CONFIRMED);
        $order->setAcceptedAt(new \DateTime());
        
        // Calculate estimated delivery time (30 minutes from now as default)
        $estimatedTime = new \DateTime();
        $estimatedTime->modify('+30 minutes');
        $order->setEstimatedDeliveryTime($estimatedTime);

        $this->em->flush();

        return $order;
    }
}


