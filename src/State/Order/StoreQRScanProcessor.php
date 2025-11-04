<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\StoreQRScanInput;
use App\Entity\OrderItemStatus;
use App\Entity\OrderStatus;
use App\Entity\QrScanLog;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreQRScanProcessor implements ProcessorInterface
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

        // Check if user is a delivery agent
        if (!in_array('ROLE_DELIVER', $user->getRoles(), true)) {
            throw new AccessDeniedHttpException('Only delivery agents can scan QR codes');
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

        /** @var StoreQRScanInput $input */
        $input = $data;

        // Get the store from the order (primary store where scan is happening)
        $store = $order->getPrimaryStore();
        if (!$store) {
            throw new BadRequestHttpException('Aucun magasin trouvé pour cette commande');
        }

        // Create log entry
        $scanLog = new QrScanLog();
        $scanLog->setAgent($user);
        $scanLog->setOrder($order);
        $scanLog->setStore($store);
        $scanLog->setScannedQrCode($input->qrCode);
        $scanLog->setScannedAt(new \DateTime());
        $scanLog->setScanType('store_pickup');

        // Verify QR code matches the order
        if ($order->getQrCode() !== $input->qrCode) {
            $scanLog->setSuccess(false);
            $scanLog->setErrorMessage('QR Code invalide pour cette commande');
            $this->em->persist($scanLog);
            $this->em->flush();
            
            throw new BadRequestHttpException('QR Code invalide pour cette commande');
        }

        // Check if all items from this store are already recuperated
        $storeItemsRecuperated = true;
        $storeHasItems = false;
        foreach ($order->getItems() as $item) {
            if ($item->getStore() === $store) {
                $storeHasItems = true;
                if ($item->getStoreStatus() !== OrderItemStatus::RECUPERATED) {
                    $storeItemsRecuperated = false;
                    break;
                }
            }
        }

        if ($storeItemsRecuperated && $storeHasItems) {
            // Store items already recuperated, but check if order should be shipped
            $this->checkAndUpdateOrderStatus($order);
            
            $scanLog->setSuccess(true);
            $scanLog->setErrorMessage(null);
            $this->em->persist($scanLog);
            $this->em->flush();
            
            return $order; // Already recuperated, return order
        }

        if (!$storeHasItems) {
            $scanLog->setSuccess(false);
            $scanLog->setErrorMessage('Aucun article trouvé pour ce magasin dans cette commande');
            $this->em->persist($scanLog);
            $this->em->flush();
            
            throw new BadRequestHttpException('Aucun article trouvé pour ce magasin dans cette commande');
        }

        // Update only OrderItemStatus for items that belong to THIS specific store
        $itemsUpdated = 0;
        foreach ($order->getItems() as $item) {
            // Only update items that belong to this store and are accepted
            if ($item->getStore() === $store && $item->getStoreStatus() === OrderItemStatus::ACCEPTED) {
                $item->setStoreStatus(OrderItemStatus::RECUPERATED);
                $item->setStoreActionAt(new \DateTime());
                $itemsUpdated++;
            }
        }

        if ($itemsUpdated === 0) {
            $scanLog->setSuccess(false);
            $scanLog->setErrorMessage('Aucun article accepté trouvé pour ce magasin dans cette commande');
            $this->em->persist($scanLog);
            $this->em->flush();
            
            throw new BadRequestHttpException('Aucun article accepté trouvé pour ce magasin dans cette commande');
        }

        // Check if all items in the order are either recuperated or refused
        // If yes, change order status to SHIPPED (expedie)
        $this->checkAndUpdateOrderStatus($order);

        // Log successful scan
        $scanLog->setSuccess(true);
        $scanLog->setErrorMessage(null);
        
        $this->em->persist($scanLog);
        $this->em->flush();

        return $order;
    }

    /**
     * Check if all items in the order are recuperated or refused
     * If yes, change order status to SHIPPED (expedie)
     */
    private function checkAndUpdateOrderStatus($order): void
    {
        $allItemsProcessed = true;
        $hasStoreItems = false;

        foreach ($order->getItems() as $item) {
            // Only check items that belong to stores
            if ($item->getStore()) {
                $hasStoreItems = true;
                // Item must be either recuperated or refused
                if ($item->getStoreStatus() !== OrderItemStatus::RECUPERATED 
                    && $item->getStoreStatus() !== OrderItemStatus::REFUSED) {
                    $allItemsProcessed = false;
                    break;
                }
            }
        }

        // If we have store items and all are processed, mark order as shipped
        if ($hasStoreItems && $allItemsProcessed) {
            $order->setStatus(OrderStatus::STATUS_SHIPPED);
            if (!$order->getPickedUpAt()) {
                $order->setPickedUpAt(new \DateTime());
            }
        }
    }
}

