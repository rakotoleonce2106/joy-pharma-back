<?php

namespace App\State\OrderItem;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SuggestOrderItemInput;
use App\Entity\OrderItemStatus;
use App\Repository\OrderItemRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\SecurityBundle\Security;

class SuggestOrderItemProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderItemRepository $orderItemRepository,
        private ProductRepository $productRepository,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var SuggestOrderItemInput $data */
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
            throw new AccessDeniedHttpException('You are not authorized to suggest changes to this order item');
        }

        // Get the suggested product
        $suggestedProduct = $this->productRepository->find($data->suggestedProductId);
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
        if ($data->suggestion) {
            $orderItem->setStoreSuggestion($data->suggestion);
        }
        $orderItem->setStorePrice($storePrice);
        if ($data->notes) {
            $orderItem->setStoreNotes($data->notes);
        }
        $orderItem->setStoreActionAt(new \DateTime());

        $this->entityManager->persist($orderItem);
        $this->entityManager->flush();

        return $orderItem;
    }
}

