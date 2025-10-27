<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\UpdateOrderStatusInput;
use App\Entity\OrderStatus;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateOrderStatusProcessor implements ProcessorInterface
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

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new NotFoundHttpException('Order not found');
        }

        // Check if the delivery person is assigned to this order
        if ($order->getDeliver() !== $user) {
            throw new AccessDeniedHttpException('You are not assigned to this order');
        }

        /** @var UpdateOrderStatusInput $input */
        $input = $data;

        // Map string status to enum
        $statusEnum = match ($input->status) {
            'pending' => OrderStatus::STATUS_PENDING,
            'confirmed' => OrderStatus::STATUS_CONFIRMED,
            'processing' => OrderStatus::STATUS_PROCESSING,
            'shipped' => OrderStatus::STATUS_SHIPPED,
            'delivered' => OrderStatus::STATUS_DELIVERED,
            'cancelled' => OrderStatus::STATUS_CANCELLED,
            default => null
        };

        if (!$statusEnum) {
            throw new BadRequestHttpException('Invalid status');
        }

        $order->setStatus($statusEnum);

        // Set timestamps based on status
        switch ($statusEnum) {
            case OrderStatus::STATUS_PROCESSING:
                if (!$order->getPickedUpAt()) {
                    $order->setPickedUpAt(new \DateTime());
                }
                break;
            case OrderStatus::STATUS_DELIVERED:
                if (!$order->getDeliveredAt()) {
                    $order->setDeliveredAt(new \DateTime());
                    $order->setActualDeliveryTime(new \DateTime());
                    
                    // Update delivery person stats
                    $user->incrementTotalDeliveries();
                    if ($order->getDeliveryFee()) {
                        $user->addEarnings((float) $order->getDeliveryFee());
                    }
                }
                break;
        }

        // Update location if provided
        if ($input->latitude && $input->longitude) {
            $user->setCurrentLatitude((string) $input->latitude);
            $user->setCurrentLongitude((string) $input->longitude);
            $user->setLastLocationUpdate(new \DateTime());
        }

        // Add notes if provided
        if ($input->notes) {
            $currentNotes = $order->getDeliveryNotes() ?? '';
            $order->setDeliveryNotes($currentNotes . "\n" . date('Y-m-d H:i:s') . ': ' . $input->notes);
        }

        $this->em->flush();

        return $order;
    }
}


