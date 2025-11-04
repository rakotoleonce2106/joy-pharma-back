<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\StoreQRScanInput;
use App\Entity\OrderStatus;
use App\Entity\QrScanLog;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\StoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreQRScanProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly StoreRepository $storeRepository,
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

        // Find store by QR code
        $store = $this->storeRepository->findOneBy(['qrCode' => $input->qrCode]);

        // Create log entry
        $scanLog = new QrScanLog();
        $scanLog->setAgent($user);
        $scanLog->setOrder($order);
        $scanLog->setScannedQrCode($input->qrCode);
        $scanLog->setScannedAt(new \DateTime());
        $scanLog->setScanType('store_pickup');

        // Verify QR code matches a store
        if (!$store) {
            $scanLog->setSuccess(false);
            $scanLog->setErrorMessage('QR Code invalide pour cette commande');
            $scanLog->setStore($order->getPrimaryStore()); // Store the expected store for reference
            $this->em->persist($scanLog);
            $this->em->flush();
            
            throw new BadRequestHttpException('QR Code invalide pour cette commande');
        }

        // Verify that the order belongs to this store
        if (!$order->belongsToStore($store)) {
            $scanLog->setStore($store);
            $scanLog->setSuccess(false);
            $scanLog->setErrorMessage('QR Code invalide pour cette commande');
            $this->em->persist($scanLog);
            $this->em->flush();
            
            throw new BadRequestHttpException('QR Code invalide pour cette commande');
        }

        // Check if order is already collected
        if ($order->getStatus() === OrderStatus::STATUS_COLLECTED) {
            $scanLog->setStore($store);
            $scanLog->setSuccess(true);
            $scanLog->setErrorMessage(null);
            $this->em->persist($scanLog);
            $this->em->flush();
            
            return $order; // Already collected, return order
        }

        // Mark order as collected
        $order->setStatus(OrderStatus::STATUS_COLLECTED);
        $order->setPickedUpAt(new \DateTime());

        // Log successful scan
        $scanLog->setStore($store);
        $scanLog->setSuccess(true);
        $scanLog->setErrorMessage(null);
        
        $this->em->persist($scanLog);
        $this->em->flush();

        return $order;
    }
}

