<?php

namespace App\EventSubscriber;

use App\Entity\PaymentStatus;
use App\Service\PaymentService;
use DahRomy\MVola\Event\MVolaCallbackEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class MvolaCallbackSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PaymentService $paymentService,
        private LoggerInterface     $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            MVolaCallbackEvent::NAME => 'onMVolaCallback',
        ];
    }

    public function onMVolaCallback(MVolaCallbackEvent $event): void
    {
        $mvolaData = $event->getMVolaData();
        $callbackData = $event->getCallbackData();

        $this->logger->info('MVola callback received', [
            'mvola_data' => $mvolaData,
            'callback_data' => $callbackData
        ]);

        try {
            $this->processCallback($mvolaData);
        } catch (\Exception $e) {
            $this->logger->error('Error processing MVola callback', [
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'mvola_data' => $mvolaData,
                'callback_data' => $callbackData
            ]);
        }
    }

    private function processCallback(array $mvolaData): void
    {
        $transactionId = $mvolaData['serverCorrelationId'] ?? null;
        $status = $mvolaData['transactionStatus'] ?? null;

        if (!$transactionId || !$status) {
            throw new \InvalidArgumentException('Invalid callback data: missing serverCorrelationId or transactionStatus');
        }

        $newStatus = $this->mapMvolaStatusToSubscriptionStatus($status);
        $this->paymentService->updatePaymentStatusByTransactionId($transactionId, $newStatus);

        $this->logger->info('Subscription status updated from MVola callback', [
            'transaction_id' => $transactionId,
            'mvola_status' => $status,
            'new_subscription_status' => $newStatus
        ]);
    }

    private function mapMvolaStatusToSubscriptionStatus(string $mvolaStatus): PaymentStatus
    {
        return match ($mvolaStatus) {
            'completed' => PaymentStatus::STATUS_COMPLETED,
            'failed' => PaymentStatus::STATUS_FAILED,
            'pending' => PaymentStatus::STATUS_PENDING,
            'refunded' => PaymentStatus::STATUS_REFUNDED,
            'processing' => PaymentStatus::STATUS_PROCESSING,
            default => throw new \InvalidArgumentException("Unknown MVola status: $mvolaStatus"),
        };
    }
}