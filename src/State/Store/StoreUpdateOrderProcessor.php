<?php

namespace App\State\Store;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\StoreOrderItemActionInput;
use App\Dto\StoreUpdateOrderInput;
use App\Entity\OrderItemStatus;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreUpdateOrderProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private OrderItemRepository $orderItemRepository,
        private StoreRepository $storeRepository,
        private ProductRepository $productRepository,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var StoreUpdateOrderInput $data */
        
        // Get the authenticated user
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be authenticated');
        }

        // Find the store owned by this user
        $store = $this->storeRepository->findOneBy(['owner' => $user]);
        if (!$store) {
            throw new NotFoundHttpException('No store found for this user');
        }

        // Get the order ID from URI variables
        $orderId = $uriVariables['id'] ?? null;
        if (!$orderId) {
            throw new BadRequestHttpException('Order ID is required in the URL path');
        }

        // Get the order
        $order = $this->orderRepository->find($orderId);
        if (!$order) {
            throw new NotFoundHttpException('Order not found');
        }

        // Process each order item action
        foreach ($data->orderItemActions as $actionData) {
            // Handle both array and object formats (API Platform can deserialize differently)
            if (is_array($actionData)) {
                $action = $this->createActionFromArray($actionData);
            } else {
                $action = $actionData;
            }

            $this->processOrderItemAction($action, $store, $order);
        }

        // Recalculate order totals
        $order->calculateTotalAmount();
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    private function createActionFromArray(array $data): StoreOrderItemActionInput
    {
        $action = new StoreOrderItemActionInput();
        $action->orderItemId = $data['orderItemId'] ?? null;
        $action->action = $data['action'] ?? null;
        $action->notes = $data['notes'] ?? null;
        $action->reason = $data['reason'] ?? null;
        $action->suggestedProductId = $data['suggestedProductId'] ?? null;
        $action->suggestion = $data['suggestion'] ?? null;
        return $action;
    }

    private function processOrderItemAction(StoreOrderItemActionInput $actionData, $store, $order): void
    {
        // Get the order item
        $orderItem = $this->orderItemRepository->find($actionData->orderItemId);
        if (!$orderItem) {
            throw new NotFoundHttpException(sprintf('Order item %d not found', $actionData->orderItemId));
        }

        // Verify that this order item belongs to the store
        if ($orderItem->getStore() !== $store) {
            throw new AccessDeniedHttpException(sprintf('You are not authorized to update order item %d', $actionData->orderItemId));
        }

        // Verify the order item belongs to the order being updated
        if ($orderItem->getOrderParent() !== $order) {
            throw new BadRequestHttpException(sprintf('Order item %d does not belong to order %d', $actionData->orderItemId, $order->getId()));
        }

        // Process based on action type
        switch ($actionData->action) {
            case 'accept':
                $this->acceptOrderItem($orderItem, $store, $actionData);
                break;
            case 'refuse':
                $this->refuseOrderItem($orderItem, $store, $actionData);
                break;
            case 'suggest':
                $this->suggestOrderItem($orderItem, $store, $actionData);
                break;
            default:
                throw new BadRequestHttpException(sprintf('Invalid action: %s. Must be one of: accept, refuse, suggest', $actionData->action));
        }
    }

    private function acceptOrderItem($orderItem, $store, StoreOrderItemActionInput $actionData): void
    {
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
        if ($actionData->notes) {
            $orderItem->setStoreNotes($actionData->notes);
        }
        $orderItem->setStoreActionAt(new \DateTime());

        $this->entityManager->persist($orderItem);
    }

    private function refuseOrderItem($orderItem, $store, StoreOrderItemActionInput $actionData): void
    {
        if (!$actionData->reason) {
            throw new BadRequestHttpException('Reason is required for refusing an order item');
        }

        // Update the order item
        $orderItem->setStoreStatus(OrderItemStatus::REFUSED);
        $orderItem->setStoreNotes($actionData->reason);
        $orderItem->setStoreActionAt(new \DateTime());

        $this->entityManager->persist($orderItem);
    }

    private function suggestOrderItem($orderItem, $store, StoreOrderItemActionInput $actionData): void
    {
        if (!$actionData->suggestedProductId) {
            throw new BadRequestHttpException('Suggested product ID is required for suggesting an alternative');
        }

        // Get the suggested product
        $suggestedProduct = $this->productRepository->find($actionData->suggestedProductId);
        if (!$suggestedProduct) {
            throw new NotFoundHttpException('Suggested product not found');
        }

        // Verify that the store has the suggested product in their inventory
        $storeProduct = $suggestedProduct->getStoreProducts()->filter(
            fn($sp) => $sp->getStore() === $store
        )->first();

        if (!$storeProduct) {
            throw new BadRequestHttpException('The suggested product is not available in your store inventory');
        }

        // Get the store's price for the suggested product
        $storePrice = $storeProduct->getPrice();
        if (!$storePrice || $storePrice <= 0) {
            throw new BadRequestHttpException('Store product has no valid price set');
        }

        // Update the order item
        $orderItem->setStoreStatus(OrderItemStatus::SUGGESTED);
        $orderItem->setSuggestedProduct($suggestedProduct);
        if ($actionData->suggestion) {
            $orderItem->setStoreSuggestion($actionData->suggestion);
        }
        $orderItem->setStorePrice($storePrice);
        if ($actionData->notes) {
            $orderItem->setStoreNotes($actionData->notes);
        }
        $orderItem->setStoreActionAt(new \DateTime());

        $this->entityManager->persist($orderItem);
    }
}

