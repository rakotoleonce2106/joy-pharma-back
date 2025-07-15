<?php

namespace App\Service;

use App\Entity\Order;
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

    public function createPaymentIntent(User $user, Order $order): array
    {
        try {
            $amount = $this->currencyService->convertToCents(
                $order->getTotalAmount()
            );
            $currency = $this->currencyService->getCurrency();
            $payment = $order->getPayment();
            $this->currencyService->validateAmount($amount, $currency->getLabel());

            $this->logger->info('Creating payment intent', [
                'user_id' => $user->getId(),
                'amount' => $amount,
                'currency' => $currency->getLabel(),
                'reference' => $order->getReference(),
                'payment_method' => $payment->getMethodLabel(),
                'phone_number' => $phoneNumber ?? null
            ]);

            switch ($payment->getMethod()) {
                default:
                    return  $this->createMvolaPaymentIntent($user,  $order, $amount, $currency->getLabel());
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to create payment intent', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'amount' => $amount,
                'currency' => $currency->getLabel(),
                'reference' => $order->getReference(),
                'payment_method' => $order->getPayment()->getMethodLabel(),
                'phone_number' => $phoneNumber ?? null
            ]);
            throw new BadRequestHttpException('Failed to create payment intent: ' . $e->getMessage(), $e);
        }
    }

    private function createMvolaPaymentIntent(User $user, Order $order,int $amount, string $currency): array
    {
        if (!$phoneNumber = $order->getPayment()->getPhoneNumber()) {
            throw new NotFoundHttpException('Phone number not found');
        }

        try {
            $mvolaPaymentIntent = $this->mvolaPaymentService->createPaymentIntent($user, $order);

            $this->logger->info('MVola payment intent created', [
                'payment_intent_id' => $mvolaPaymentIntent['id'],
                'user_id' => $user->getId(),
                'phone_number' => $phoneNumber
            ]);
            $payment = $order->getPayment();
            $payment->setTransactionId($mvolaPaymentIntent['id']);
            $this->paymentService->createPayment($payment);
            $order->setPayment($payment);
            $this->orderService->updateOrder($order);

            return [
                'id' => $mvolaPaymentIntent['id'],
                'amount' => $amount,
                'currency' => $currency,
                'clientSecret' => $mvolaPaymentIntent['client_secret'],
                'status' => $mvolaPaymentIntent['status'],
                'provider' => $order->getPayment()->getMethodLabel(),
                'reference' => $order->getReference()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create MVola payment intent', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
                'phone_number' => $phoneNumber,
                'provider' => $order->getPayment()->getMethodLabel(),
                'reference' => $order->getReference()
            ]);
            throw $e;
        }
    }
}
