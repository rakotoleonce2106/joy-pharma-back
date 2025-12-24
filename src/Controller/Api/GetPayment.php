<?php

namespace App\Controller\Api;

use App\Service\OrderService;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class GetPayment extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
        private readonly SerializerInterface $serializer
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

        $json = $this->serializer->serialize($payment, 'json', [
            'groups' => ['id:read', 'payment:read', 'order:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
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

        $json = $this->serializer->serialize($payment, 'json', [
            'groups' => ['id:read', 'payment:read', 'order:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}

