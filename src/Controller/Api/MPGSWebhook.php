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

class MPGSWebhook extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/api/mpgs-webhook', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                $this->logger->warning('MPGS webhook received invalid JSON', [
                    'content' => $request->getContent()
                ]);
                return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            $this->logger->info('MPGS webhook received', ['data' => $data]);

            // Extract webhook data
            $orderId = $data['order']['id'] ?? null;
            $transactionId = $data['transaction']['id'] ?? null;
            $result = $data['result'] ?? null;
            $resultIndicator = $data['resultIndicator'] ?? null;

            if (!$orderId) {
                $this->logger->error('MPGS webhook missing order ID', ['data' => $data]);
                return new JsonResponse(['error' => 'Missing order ID'], Response::HTTP_BAD_REQUEST);
            }

            // Find payment by order reference or transaction ID
            $order = $this->orderService->findByReference($orderId);
            if (!$order && $transactionId) {
                // Try to find by transaction ID if order not found by reference
                $payment = $this->paymentService->findByTransactionId($transactionId);
                if ($payment) {
                    $order = $payment->getOrder();
                }
            }

            if (!$order) {
                $this->logger->error('MPGS webhook: Order not found', [
                    'orderId' => $orderId,
                    'transactionId' => $transactionId
                ]);
                return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
            }

            $payment = $order->getPayment();
            if (!$payment) {
                $this->logger->error('MPGS webhook: Payment not found for order', [
                    'orderId' => $orderId
                ]);
                return new JsonResponse(['error' => 'Payment not found'], Response::HTTP_NOT_FOUND);
            }

            // Verify successIndicator if provided
            if ($resultIndicator && $payment->getGatewayResponse()) {
                $gatewayData = json_decode($payment->getGatewayResponse(), true);
                $storedSuccessIndicator = $gatewayData['successIndicator'] ?? null;

                if ($storedSuccessIndicator && $resultIndicator !== $storedSuccessIndicator) {
                    $this->logger->warning('MPGS webhook: successIndicator mismatch', [
                        'received' => $resultIndicator,
                        'stored' => $storedSuccessIndicator,
                        'orderId' => $orderId
                    ]);
                    // Don't reject, but log the mismatch
                }
            }

            // Map MPGS result to payment status
            $paymentStatus = $this->mapMPGSResultToPaymentStatus($result);
            
            // Update payment status
            $payment->setStatus($paymentStatus);
            if ($transactionId) {
                $payment->setTransactionId($transactionId);
            }
            
            // Store webhook data in gatewayResponse
            $webhookData = [
                'webhook_received_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'result' => $result,
                'resultIndicator' => $resultIndicator,
                'transaction_id' => $transactionId,
                'order_data' => $data['order'] ?? null,
                'transaction_data' => $data['transaction'] ?? null
            ];
            $payment->setGatewayResponse(json_encode($webhookData));
            
            $this->paymentService->updatePayment($payment);

            // Update order status if payment is completed
            if ($paymentStatus === PaymentStatus::STATUS_COMPLETED) {
                $this->updateOrderAfterPayment($order);
            }

            $this->logger->info('MPGS webhook processed successfully', [
                'orderId' => $orderId,
                'transactionId' => $transactionId,
                'result' => $result,
                'paymentStatus' => $paymentStatus->value
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Webhook processed',
                'orderId' => $orderId,
                'status' => $paymentStatus->value
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing MPGS webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function mapMPGSResultToPaymentStatus(string $result): PaymentStatus
    {
        return match (strtoupper($result)) {
            'SUCCESS', 'CAPTURED', 'AUTHORIZED' => PaymentStatus::STATUS_COMPLETED,
            'PENDING', 'IN_PROGRESS' => PaymentStatus::STATUS_PROCESSING,
            'FAILURE', 'DECLINED', 'ERROR', 'CANCELLED' => PaymentStatus::STATUS_FAILED,
            'REFUNDED', 'REVERSED' => PaymentStatus::STATUS_REFUNDED,
            default => PaymentStatus::STATUS_FAILED,
        };
    }

    private function updateOrderAfterPayment(Order $order): void
    {
        // Update order status to confirmed when payment is completed
        // You can customize this based on your business logic
        if ($order->getStatus()->value === 'pending') {
            $order->setStatus(\App\Entity\OrderStatus::STATUS_CONFIRMED);
            $this->orderService->updateOrder($order);
            
            $this->logger->info('Order status updated after payment', [
                'orderId' => $order->getReference(),
                'newStatus' => 'confirmed'
            ]);
        }
    }
}

