<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * EventSubscriber pour envoyer des notifications lors de la création et mise à jour de commandes
 */
#[AsEntityListener(event: Events::postPersist, method: 'onOrderCreated', entity: Order::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onOrderUpdated', entity: Order::class)]
readonly class OrderNotificationSubscriber
{
    public function __construct(
        private NotificationService $notificationService,
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Déclenche une notification lorsqu'une nouvelle commande est créée
     */
    public function onOrderCreated(Order $order, PostPersistEventArgs $event): void
    {
        try {
            $owner = $order->getOwner();
            if (!$owner) {
                return;
            }

            // Notifier le client de la création de sa commande
            $this->notificationService->sendNotification(
                $owner,
                'Commande créée',
                "Votre commande {$order->getReference()} a été créée avec succès",
                'order_new',
                [
                    'orderId' => $order->getId(),
                    'orderReference' => $order->getReference(),
                    'totalAmount' => $order->getTotalAmount(),
                ],
                ['sendPush' => true, 'sendEmail' => false]
            );

            // Notifier les propriétaires de magasins concernés
            $stores = $order->getStores();
            foreach ($stores as $store) {
                $storeOwner = $store->getOwner();
                if ($storeOwner) {
                    $this->notificationService->sendNewOrderNotification(
                        $storeOwner,
                        $order->getReference(),
                        $order->getTotalAmount(),
                        ['sendPush' => true, 'sendEmail' => true]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error sending order creation notification', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Déclenche une notification lorsqu'une commande est mise à jour (changement de statut notamment)
     */
    public function onOrderUpdated(Order $order, PostUpdateEventArgs $event): void
    {
        try {
            $owner = $order->getOwner();
            if (!$owner) {
                return;
            }

            // Vérifier si le statut a changé
            $changeSet = $event->getObjectManager()->getUnitOfWork()->getEntityChangeSet($order);
            
            // Delivery assignment change
            if (isset($changeSet['deliver'])) {
                $oldDeliver = $changeSet['deliver'][0] ?? null;
                $newDeliver = $changeSet['deliver'][1] ?? null;

                if ($newDeliver && $newDeliver !== $oldDeliver) {
                    $this->notificationService->sendNotification(
                        $newDeliver,
                        'Commande assignée',
                        "Une commande {$order->getReference()} vous a été assignée",
                        'order_new',
                        [
                            'orderId' => $order->getId(),
                            'orderReference' => $order->getReference(),
                        ],
                        ['sendPush' => true, 'sendEmail' => false]
                    );
                }
            }

            if (isset($changeSet['status'])) {
                $oldStatus = $changeSet['status'][0];
                $newStatus = $changeSet['status'][1];

                // Notifier le client du changement de statut
                $this->notificationService->sendOrderStatusNotification(
                    $owner,
                    $order->getReference(),
                    $newStatus->value,
                    ['sendPush' => true, 'sendEmail' => $newStatus->value === 'delivered']
                );

                $deliver = $order->getDeliver();
                $stores = $order->getStores();

                // Your requested rules
                switch ($newStatus) {
                    case OrderStatus::STATUS_PROCESSING:
                        // Keep existing customer status notification; store assignment is handled on deliver selection.
                        break;

                    case OrderStatus::STATUS_COLLECTED:
                        // order picked up from store by deliver -> notify store + admins + deliver
                        $admins = $this->userRepository->findByRole('ROLE_ADMIN');
                        foreach ($stores as $store) {
                            $storeOwner = $store->getOwner();
                            if ($storeOwner) {
                                $this->notificationService->sendNotification(
                                    $storeOwner,
                                    'Commande récupérée',
                                    "La commande {$order->getReference()} a été récupérée par le livreur",
                                    'order_status',
                                    [
                                        'orderId' => $order->getId(),
                                        'orderReference' => $order->getReference(),
                                        'status' => $newStatus->value,
                                    ],
                                    ['sendPush' => true, 'sendEmail' => false]
                                );
                            }
                        }

                        foreach ($admins as $admin) {
                            $this->notificationService->sendNotification(
                                $admin,
                                'Commande récupérée',
                                "La commande {$order->getReference()} a été récupérée au magasin",
                                'order_status',
                                [
                                    'orderId' => $order->getId(),
                                    'orderReference' => $order->getReference(),
                                    'status' => $newStatus->value,
                                ],
                                ['sendPush' => true, 'sendEmail' => false]
                            );
                        }

                        if ($deliver) {
                            $this->notificationService->sendNotification(
                                $deliver,
                                'Commande récupérée',
                                "Vous avez récupéré la commande {$order->getReference()}",
                                'order_status',
                                [
                                    'orderId' => $order->getId(),
                                    'orderReference' => $order->getReference(),
                                    'status' => $newStatus->value,
                                ],
                                ['sendPush' => true, 'sendEmail' => false]
                            );
                        }
                        break;

                    case OrderStatus::STATUS_DELIVERED:
                        // order delivered -> notify user + admins + deliver
                        $admins = $this->userRepository->findByRole('ROLE_ADMIN');
                        foreach ($admins as $admin) {
                            $this->notificationService->sendNotification(
                                $admin,
                                'Commande livrée',
                                "La commande {$order->getReference()} a été livrée",
                                'order_status',
                                [
                                    'orderId' => $order->getId(),
                                    'orderReference' => $order->getReference(),
                                    'status' => $newStatus->value,
                                ],
                                ['sendPush' => true, 'sendEmail' => false]
                            );
                        }

                        if ($deliver) {
                            $this->notificationService->sendNotification(
                                $deliver,
                                'Commande livrée',
                                "La commande {$order->getReference()} a été livrée",
                                'order_status',
                                [
                                    'orderId' => $order->getId(),
                                    'orderReference' => $order->getReference(),
                                    'status' => $newStatus->value,
                                ],
                                ['sendPush' => true, 'sendEmail' => false]
                            );
                        }
                        break;

                    default:
                        // no-op
                        break;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error sending order update notification', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}

