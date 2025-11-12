<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class PaymentIntentService
{
    public function __construct(
        private MvolaPaymentService $mvolaPaymentService,
        private OrderService         $orderService,
        private CurrencyService     $currencyService,
        private PaymentService      $paymentService,
        private LoggerInterface     $logger
    ) {}

    public function createPaymentIntent(User $user, Payment $payment): array
    {
        try {
            $amount = $this->currencyService->convertToCents(
                $payment->getAmount()
            );
            $currency = $this->currencyService->getCurrency();
            $this->currencyService->validateAmount($amount, $currency->getLabel());

            $phoneNumber = $payment->getPhoneNumber();

            $this->logger->info('Creating payment intent', [
                'user_id' => $user->getId(),
                'amount' => $amount,
                'currency' => $currency->getLabel(),
                'reference' => $payment->getReference(),
                'payment_method' => $payment->getMethodLabel(),
                'phone_number' => $phoneNumber ?? null
            ]);

            switch ($payment->getMethod()) {
                default:
                    return $this->createMvolaPaymentIntent($user, $payment, $amount, $currency->getLabel());
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
}
