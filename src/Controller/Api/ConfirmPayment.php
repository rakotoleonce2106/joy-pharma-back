<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\PaymentStatus;
use App\Service\OrderService;
use App\Service\PaymentService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ConfirmPayment extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/api/confirm-payment', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            $orderId = $data['orderId'] ?? null;
            $resultIndicator = $data['resultIndicator'] ?? null;

            if (!$orderId) {
                return new JsonResponse(['error' => 'Missing orderId'], Response::HTTP_BAD_REQUEST);
            }

            // Find order and payment
            $order = $this->orderService->findByReference($orderId);
            if (!$order) {
                return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
            }

            $payment = $order->getPayment();
            if (!$payment) {
                return new JsonResponse(['error' => 'Payment not found'], Response::HTTP_NOT_FOUND);
            }

            // Verify successIndicator for MPGS payments
            if ($payment->getMethod()->value === 'mpgs' && $resultIndicator) {
                $gatewayData = json_decode($payment->getGatewayResponse(), true);
                $storedSuccessIndicator = $gatewayData['successIndicator'] ?? null;

                if (!$storedSuccessIndicator) {
                    $this->logger->warning('MPGS payment confirmation: No successIndicator stored', [
                        'orderId' => $orderId
                    ]);
                    return new JsonResponse([
                        'error' => 'Payment verification failed: Missing successIndicator'
                    ], Response::HTTP_BAD_REQUEST);
                }

                if ($resultIndicator !== $storedSuccessIndicator) {
                    $this->logger->warning('MPGS payment confirmation: successIndicator mismatch', [
                        'orderId' => $orderId,
                        'received' => $resultIndicator,
                        'stored' => $storedSuccessIndicator
                    ]);
                    return new JsonResponse([
                        'error' => 'Payment verification failed: Invalid resultIndicator'
                    ], Response::HTTP_BAD_REQUEST);
                }

                // Payment verified successfully
                $payment->setStatus(PaymentStatus::STATUS_COMPLETED);
                $this->paymentService->updatePayment($payment);

                // Update order status
                $this->updateOrderAfterPayment($order);

                $this->logger->info('MPGS payment confirmed successfully', [
                    'orderId' => $orderId,
                    'resultIndicator' => $resultIndicator
                ]);

                return new JsonResponse($this->serializer->serialize($payment, 'json', [
                    'groups' => ['id:read', 'payment:read', 'order:read']
                ]), Response::HTTP_OK, [], true);
            }

            // For non-MPGS payments or if resultIndicator not provided
            // Just update status if payment is already completed
            if ($payment->isCompleted()) {
                $this->updateOrderAfterPayment($order);
                return new JsonResponse($this->serializer->serialize($payment, 'json', [
                    'groups' => ['id:read', 'payment:read', 'order:read']
                ]), Response::HTTP_OK, [], true);
            }

            return new JsonResponse([
                'success' => false,
                'message' => 'Payment not completed',
                'orderId' => $orderId,
                'status' => $payment->getStatus()->value
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error confirming payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function updateOrderAfterPayment(Order $order): void
    {
        if ($order->getStatus()->value === 'pending') {
            $order->setStatus(\App\Entity\OrderStatus::STATUS_CONFIRMED);
            $this->orderService->updateOrder($order);
        }
    }
}

