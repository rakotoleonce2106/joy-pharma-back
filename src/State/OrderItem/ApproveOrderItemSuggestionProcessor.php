<?php

namespace App\State\OrderItem;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ApproveOrderItemSuggestionInput;
use App\Entity\OrderItemStatus;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\SecurityBundle\Security;

class ApproveOrderItemSuggestionProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderItemRepository $orderItemRepository,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var ApproveOrderItemSuggestionInput $data */
        $orderItem = $this->orderItemRepository->find($data->orderItemId);

        if (!$orderItem) {
            throw new NotFoundHttpException('Order item not found');
        }

        // Verify that the current user is an admin
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only admins can approve suggestions');
        }

        // Verify that the order item has a suggestion
        if ($orderItem->getStoreStatus() !== OrderItemStatus::SUGGESTED) {
            throw new BadRequestHttpException('Order item must be in SUGGESTED status to be approved');
        }

        // Verify that a suggested product exists
        if (!$orderItem->getSuggestedProduct()) {
            throw new BadRequestHttpException('No suggested product found');
        }

        // Replace the original product with the suggested product
        $orderItem->setProduct($orderItem->getSuggestedProduct());
        
        // Reset to PENDING status so store can accept with their price
        $orderItem->setStoreStatus(OrderItemStatus::PENDING);
        
        // Add admin notes
        if ($data->adminNotes) {
            $currentNotes = $orderItem->getStoreNotes() ?? '';
            $orderItem->setStoreNotes($currentNotes . "\n[Admin Approved]: " . $data->adminNotes);
        }

        // Clear the suggested product and store price (will be set when store accepts)
        $orderItem->setSuggestedProduct(null);
        $orderItem->setStorePrice(null);

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

