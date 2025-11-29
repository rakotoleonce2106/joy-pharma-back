<?php

namespace App\EventSubscriber;

use App\Entity\Order;
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

            // Déclencher l'événement n8n
            $this->notificationService->getN8nService()->triggerEvent('order.created', [
                'orderId' => $order->getId(),
                'orderReference' => $order->getReference(),
                'customerId' => $owner->getId(),
                'customerEmail' => $owner->getEmail(),
                'totalAmount' => $order->getTotalAmount(),
                'status' => $order->getStatus()->value,
            ]);
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

                // Notifier le livreur si une commande est assignée
                $deliver = $order->getDeliver();
                if ($deliver && in_array($newStatus->value, ['confirmed', 'processing', 'shipped'])) {
                    $this->notificationService->sendNotification(
                        $deliver,
                        'Nouvelle commande assignée',
                        "Une commande {$order->getReference()} vous a été assignée",
                        'order_new',
                        [
                            'orderId' => $order->getId(),
                            'orderReference' => $order->getReference(),
                        ],
                        ['sendPush' => true]
                    );
                }

                // Déclencher l'événement n8n
                $this->notificationService->getN8nService()->triggerEvent('order.status_changed', [
                    'orderId' => $order->getId(),
                    'orderReference' => $order->getReference(),
                    'oldStatus' => $oldStatus->value,
                    'newStatus' => $newStatus->value,
                    'customerId' => $owner->getId(),
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error sending order update notification', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}

