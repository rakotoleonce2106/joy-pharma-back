<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * EventSubscriber for Order entity lifecycle events
 * Handles:
 * - Reference uniqueness validation on create
 * - Auto-generate reference if not provided
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Order::class)]
class OrderSubscriber
{
    public function __construct(
        private readonly OrderRepository $orderRepository
    ) {}

    /**
     * Validate reference uniqueness and auto-generate if not provided before persisting (create)
     */
    public function prePersist(Order $order, PrePersistEventArgs $event): void
    {
        // If reference is not provided, generate one
        if (!$order->getReference() || empty(trim($order->getReference()))) {
            $order->setReference($this->generateReference());
        }

        // Check if reference already exists
        $existingOrder = $this->orderRepository->findOneBy(['reference' => $order->getReference()]);
        if ($existingOrder) {
            throw new BadRequestHttpException('Order with this reference already exists');
        }

        // Ensure QR code is generated if reference was just set
        if (!$order->getQrCode()) {
            $order->setQrCode($this->generateQRCode($order->getReference()));
        }
    }

    /**
     * Generate a unique order reference
     */
    private function generateReference(): string
    {
        return 'ORD-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate QR code for order
     */
    private function generateQRCode(string $reference): string
    {
        return 'ORDER-' . $reference . '-' . strtoupper(bin2hex(random_bytes(8)));
    }
}

