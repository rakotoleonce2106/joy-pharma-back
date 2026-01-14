<?php

namespace App\EventSubscriber;

use App\Entity\Payment;
use App\Entity\PaymentStatus;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * Sends push notifications when a payment status changes (e.g. completed).
 */
#[AsEntityListener(event: Events::postUpdate, method: 'onPaymentUpdated', entity: Payment::class)]
readonly class PaymentNotificationSubscriber
{
    public function __construct(
        private NotificationService $notificationService,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function onPaymentUpdated(Payment $payment, PostUpdateEventArgs $event): void
    {
        try {
            $changeSet = $event->getObjectManager()->getUnitOfWork()->getEntityChangeSet($payment);

            if (!isset($changeSet['status'])) {
                return;
            }

            /** @var PaymentStatus $oldStatus */
            $oldStatus = $changeSet['status'][0];
            /** @var PaymentStatus $newStatus */
            $newStatus = $changeSet['status'][1];

            if ($newStatus !== PaymentStatus::STATUS_COMPLETED || $oldStatus === $newStatus) {
                return;
            }

            $order = $payment->getOrder();
            if (!$order) {
                return;
            }

            $owner = $order->getOwner();
            if (!$owner) {
                return;
            }

            // Notify customer
            $this->notificationService->sendNotification(
                $owner,
                'Paiement confirmé',
                "Votre paiement pour la commande {$order->getReference()} a été confirmé",
                'order_status',
                [
                    'orderId' => $order->getId(),
                    'orderReference' => $order->getReference(),
                    'paymentStatus' => $newStatus->value,
                ],
                ['sendPush' => true, 'sendEmail' => false]
            );

            // Notify admins
            $admins = $this->userRepository->findByRole('ROLE_ADMIN');
            foreach ($admins as $admin) {
                $this->notificationService->sendNotification(
                    $admin,
                    'Commande payée',
                    "La commande {$order->getReference()} a été payée",
                    'order_status',
                    [
                        'orderId' => $order->getId(),
                        'orderReference' => $order->getReference(),
                        'paymentStatus' => $newStatus->value,
                    ],
                    ['sendPush' => true, 'sendEmail' => false]
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error('Error sending payment notification', [
                'payment_id' => $payment->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}

