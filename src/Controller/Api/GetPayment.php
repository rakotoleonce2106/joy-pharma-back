<?php

namespace App\Controller\Api;

use App\Service\OrderService;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetPayment extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService
    ) {}

    #[Route('/api/payment/order/{orderId}', methods: ['GET'])]
    public function getByOrderId(string $orderId): JsonResponse
    {
        $order = $this->orderService->findByReference($orderId);
        
        if (!$order) {
            return new JsonResponse([
                'error' => 'Order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $payment = $order->getPayment();
        
        if (!$payment) {
            return new JsonResponse([
                'error' => 'Payment not found for this order'
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $payment->getId(),
            'transactionId' => $payment->getTransactionId(),
            'amount' => $payment->getAmount(),
            'method' => $payment->getMethod()->value,
            'status' => $payment->getStatus()->value,
            'reference' => $payment->getReference(),
            'createdAt' => $payment->getCreatedAt()?->format('Y-m-d H:i:s'),
            'processedAt' => $payment->getProcessedAt()?->format('Y-m-d H:i:s'),
            'orderId' => $orderId
        ]);
    }

    #[Route('/api/payment/transaction/{transactionId}', methods: ['GET'])]
    public function getByTransactionId(string $transactionId): JsonResponse
    {
        $payment = $this->paymentService->findByTransactionId($transactionId);
        
        if (!$payment) {
            return new JsonResponse([
                'error' => 'Payment not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $order = $payment->getOrder();

        return new JsonResponse([
            'id' => $payment->getId(),
            'transactionId' => $payment->getTransactionId(),
            'amount' => $payment->getAmount(),
            'method' => $payment->getMethod()->value,
            'status' => $payment->getStatus()->value,
            'reference' => $payment->getReference(),
            'createdAt' => $payment->getCreatedAt()?->format('Y-m-d H:i:s'),
            'processedAt' => $payment->getProcessedAt()?->format('Y-m-d H:i:s'),
            'orderId' => $order?->getReference()
        ]);
    }
}

