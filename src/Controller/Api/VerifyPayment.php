<?php

namespace App\Controller\Api;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VerifyPayment extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly SerializerInterface $serializer
    ) {}

    #[Route('/api/verify-payment/{orderId}', methods: ['GET'])]
    public function __invoke(string $orderId, Request $request): JsonResponse
    {
        $order = $this->orderService->findByReference($orderId);
        
        if (!$order) {
            return new JsonResponse([
                'verified' => false,
                'error' => 'Order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $payment = $order->getPayment();
        
        if (!$payment) {
            return new JsonResponse([
                'verified' => false,
                'error' => 'Payment not found for this order'
            ], Response::HTTP_NOT_FOUND);
        }

        // For MPGS payments, optionally verify resultIndicator
        $resultIndicator = $request->query->get('resultIndicator');
        if ($payment->getMethod()->value === 'mpgs' && $resultIndicator) {
            $gatewayData = json_decode($payment->getGatewayResponse(), true);
            $storedSuccessIndicator = $gatewayData['successIndicator'] ?? null;

            if ($storedSuccessIndicator && $resultIndicator !== $storedSuccessIndicator) {
                 return new JsonResponse([
                    'verified' => false,
                    'error' => 'resultIndicator mismatch',
                    'status' => $payment->getStatus()->value
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $json = $this->serializer->serialize($payment, 'json', [
            'groups' => ['id:read', 'payment:read', 'order:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}

