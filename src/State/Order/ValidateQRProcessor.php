<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ValidateQRInput;
use App\Entity\OrderStatus;
use App\Entity\QrScanLog;
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

        /** @var ValidateQRInput $input */
        $input = $data;

        // Create log entry for this scan attempt
        $scanLog = new QrScanLog();
        $scanLog->setAgent($user);
        $scanLog->setOrder($order);
        $scanLog->setCustomer($order->getOwner()); // Customer who owns the order
        $scanLog->setScannedQrCode($input->qrCode);
        $scanLog->setScannedAt(new \DateTime());
        $scanLog->setScanType('customer_delivery');

        // Add geolocation if provided
        if ($input->latitude !== null) {
            $scanLog->setLatitude((string) $input->latitude);
        }
        if ($input->longitude !== null) {
            $scanLog->setLongitude((string) $input->longitude);
        }

        // Verify QR code matches the order
        if ($order->getQrCode() !== $input->qrCode) {
            $scanLog->setSuccess(false);
            $scanLog->setErrorMessage('QR Code invalide pour cette commande');
            $this->em->persist($scanLog);
            $this->em->flush();
            
            throw new BadRequestHttpException('QR Code invalide pour cette commande');
        }

        // Check if already validated/delivered
        if ($order->getQrCodeValidatedAt()) {
            $scanLog->setSuccess(true);
            $scanLog->setErrorMessage(null);
            $this->em->persist($scanLog);
            $this->em->flush();
            
            // Return order even if already validated
            return $order;
        }

        // Mark QR code as validated
        $order->setQrCodeValidatedAt(new \DateTime());
        
        // Mark order as delivered / Commande Récupérée
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

        // Log successful scan
        $scanLog->setSuccess(true);
        $scanLog->setErrorMessage(null);
        $this->em->persist($scanLog);
        $this->em->flush();

        return $order;
    }
}






