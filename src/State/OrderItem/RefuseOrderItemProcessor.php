<?php

namespace App\State\OrderItem;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\RefuseOrderItemInput;
use App\Entity\OrderItemStatus;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\SecurityBundle\Security;

class RefuseOrderItemProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderItemRepository $orderItemRepository,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var RefuseOrderItemInput $data */
        $orderItem = $this->orderItemRepository->find($data->orderItemId);

        if (!$orderItem) {
            throw new NotFoundHttpException('Order item not found');
        }

        // Verify that the current user is the store owner for this order item
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be authenticated');
        }

        $store = $orderItem->getStore();
        if (!$store || $store->getOwner() !== $user) {
            throw new AccessDeniedHttpException('You are not authorized to refuse this order item');
        }

        // Update the order item
        $orderItem->setStoreStatus(OrderItemStatus::REFUSED);
        $orderItem->setStoreNotes($data->reason);
        $orderItem->setStoreActionAt(new \DateTime());

        $this->entityManager->persist($orderItem);
        
        // Recalculate order totals
        $order = $orderItem->getOrderParent();
        if ($order) {
            $order->calculateTotalAmount();
            $this->entityManager->persist($order);
        }
        
        $this->entityManager->flush();

        return $orderItem;
    }
}

