<?php

namespace App\State\OrderItem;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\AcceptOrderItemInput;
use App\Entity\OrderItemStatus;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\SecurityBundle\Security;

class AcceptOrderItemProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderItemRepository $orderItemRepository,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var AcceptOrderItemInput $data */
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
            throw new AccessDeniedHttpException('You are not authorized to accept this order item');
        }

        // Verify that the store has this product in their inventory
        $product = $orderItem->getProduct();
        if (!$product) {
            throw new BadRequestHttpException('Order item has no product');
        }

        $storeProduct = $product->getStoreProducts()->filter(
            fn($sp) => $sp->getStore() === $store
        )->first();

        if (!$storeProduct) {
            throw new BadRequestHttpException('This product is not available in your store. You can suggest an alternative product instead.');
        }

        // Use the store's product price
        $storePrice = $storeProduct->getPrice();
        if (!$storePrice || $storePrice <= 0) {
            throw new BadRequestHttpException('Store product has no valid price set');
        }

        // Update the order item
        $orderItem->setStoreStatus(OrderItemStatus::ACCEPTED);
        $orderItem->setStorePrice($storePrice);
        if ($data->notes) {
            $orderItem->setStoreNotes($data->notes);
        }
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

