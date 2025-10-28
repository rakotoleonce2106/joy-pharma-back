<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ValidateQRInput;
use App\Entity\OrderStatus;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateQRProcessor implements ProcessorInterface
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

        /** @var ValidateQRInput $input */
        $input = $data;

        // Validate QR code
        if ($order->getQrCode() !== $input->qrCode) {
            throw new BadRequestHttpException('Invalid QR code');
        }

        // Check if already validated
        if ($order->getQrCodeValidatedAt()) {
            throw new BadRequestHttpException('QR code already validated');
        }

        // Mark QR code as validated
        $order->setQrCodeValidatedAt(new \DateTime());
        
        // Automatically mark as delivered
        if ($order->getStatus() !== OrderStatus::STATUS_DELIVERED) {
            $order->setStatus(OrderStatus::STATUS_DELIVERED);
            $order->setDeliveredAt(new \DateTime());
            $order->setActualDeliveryTime(new \DateTime());
            
            // Update delivery person stats
            $user->incrementTotalDeliveries();
            if ($order->getDeliveryFee()) {
                $user->addEarnings((float) $order->getDeliveryFee());
            }
        }

        $this->em->flush();

        return $order;
    }
}





