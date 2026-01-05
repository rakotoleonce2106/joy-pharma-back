<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\PaymentMethod;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class PaymentIntentService
{
    public function __construct(
        private MvolaPaymentService $mvolaPaymentService,
        private MPGSPaymentService  $mpgsPaymentService,
        private OrderService         $orderService,
        private CurrencyService     $currencyService,
        private PaymentService      $paymentService,
        private ParameterBagInterface $params,
        private LoggerInterface     $logger
    ) {}

    public function createPaymentIntent(User $user, Payment $payment): array
    {
        try {
            $amount = $this->currencyService->convertToCents(
                $payment->getAmount()
            );
            
            // Get currency - use MPGS default for MPGS payments, otherwise get from service
            $currencyLabel = 'USD'; // Default fallback
            if ($payment->getMethod() === PaymentMethod::METHOD_MPGS) {
                // For MPGS, use the configured default currency
                $currencyLabel = $this->params->get('mpgs.default_currency', 'USD');
            } else {
                // For other payment methods, get currency from database
                $currency = $this->currencyService->getCurrency();
                if ($currency) {
                    $currencyLabel = $currency->getIsoCode() ?? 'USD';
                }
            }
            
            $this->currencyService->validateAmount($amount, $currencyLabel);

            $phoneNumber = $payment->getPhoneNumber();

            $this->logger->info('Creating payment intent', [
                'user_id' => $user->getId(),
                'amount' => $amount,
                'currency' => $currencyLabel,
                'reference' => $payment->getReference(),
                'payment_method' => $payment->getMethodLabel(),
                'phone_number' => $phoneNumber ?? null
            ]);

            switch ($payment->getMethod()) {
                case PaymentMethod::METHOD_MPGS:
                    return $this->createMPGSPaymentIntent($user, $payment, $amount, $currencyLabel);
                case PaymentMethod::METHODE_MVOLA:
                default:
                    return $this->createMvolaPaymentIntent($user, $payment, $amount, $currencyLabel);
            }
        } catch (\Exception $e) {
            $phoneNumber = $payment->getPhoneNumber();
            $this->logger->error('Failed to create payment intent', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'amount' => $amount ?? null,
                'reference' => $payment->getReference(),
                'payment_method' => $payment->getMethodLabel(),
                'phone_number' => $phoneNumber ?? null
            ]);
            throw new BadRequestHttpException('Failed to create payment intent: ' . $e->getMessage(), $e);
        }
    }

    private function createMvolaPaymentIntent(User $user, Payment $payment, int $amount, string $currency): array
    {
        if (!$phoneNumber = $payment->getPhoneNumber()) {
            throw new NotFoundHttpException('Phone number not found');
        }

        try {
            $mvolaPaymentIntent = $this->mvolaPaymentService->createPaymentIntent($user, $payment, $amount, $currency);

            $this->logger->info('MVola payment intent created', [
                'payment_intent_id' => $mvolaPaymentIntent['id'],
                'user_id' => $user->getId(),
                'phone_number' => $phoneNumber
            ]);
            $payment->setTransactionId($mvolaPaymentIntent['id']);
            $this->paymentService->createPayment($payment);

            return [
                'id' => $mvolaPaymentIntent['id'],
                'clientSecret' => $mvolaPaymentIntent['client_secret'],
                'status' => $mvolaPaymentIntent['status'],
                'provider' => $payment->getMethodLabel(),
                'reference' => $payment->getReference()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create MVola payment intent', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber,
                'provider' => $payment->getMethodLabel(),
                'reference' => $payment->getReference()
            ]);
            throw $e;
        }
    }

    private function createMPGSPaymentIntent(User $user, Payment $payment, int $amount, string $currency): array
    {
        try {
            $mpgsPaymentIntent = $this->mpgsPaymentService->createPaymentIntent($user, $payment, $amount, $currency);

            $this->logger->info('MPGS payment intent created', [
                'payment_intent_id' => $mpgsPaymentIntent['id'],
                'user_id' => $user->getId(),
                'order_id' => $mpgsPaymentIntent['order_id'] ?? null
            ]);

            $payment->setTransactionId($mpgsPaymentIntent['id']);
            
            // Store MPGS session data in gatewayResponse for verification
            $mpgsData = [
                'sessionId' => $mpgsPaymentIntent['session_id'] ?? null,
                'sessionVersion' => $mpgsPaymentIntent['session_version'] ?? null,
                'successIndicator' => $mpgsPaymentIntent['success_indicator'] ?? null,
                'orderId' => $mpgsPaymentIntent['order_id'] ?? null
            ];
            $payment->setGatewayResponse(json_encode($mpgsData));
            
            $this->paymentService->createPayment($payment);

            $result = [
                'id' => $mpgsPaymentIntent['id'],
                'clientSecret' => $mpgsPaymentIntent['client_secret'],
                'status' => $mpgsPaymentIntent['status'],
                'provider' => $payment->getMethodLabel(),
                'reference' => $payment->getReference()
            ];

            // Add MPGS-specific fields if available
            if (isset($mpgsPaymentIntent['session_id'])) {
                $result['sessionId'] = $mpgsPaymentIntent['session_id'];
            }
            if (isset($mpgsPaymentIntent['session_version'])) {
                $result['sessionVersion'] = $mpgsPaymentIntent['session_version'];
            }
            if (isset($mpgsPaymentIntent['success_indicator'])) {
                $result['successIndicator'] = $mpgsPaymentIntent['success_indicator'];
            }
            if (isset($mpgsPaymentIntent['order_id'])) {
                $result['orderId'] = $mpgsPaymentIntent['order_id'];
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create MPGS payment intent', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
                'provider' => $payment->getMethodLabel(),
                'reference' => $payment->getReference()
            ]);
            throw $e;
        }
    }
}
