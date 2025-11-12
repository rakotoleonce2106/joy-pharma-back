<?php

namespace App\Controller\Api;

use App\Entity\Payment;
use App\Entity\PaymentMethod;
use App\Service\OrderService;
use App\Service\PaymentIntentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class CreatePaymentIntent extends AbstractController
{
    public function __construct(private readonly PaymentIntentService $paymentIntentService,
    private readonly OrderService $orderService
    ) {}

    public function __invoke(#[MapRequestPayload] Payment $payment): JsonResponse
    {
        $paymentMethod = $payment->getMethod();
        if ($paymentMethod !== PaymentMethod::METHODE_MVOLA) {
            throw new BadRequestHttpException('Invalid payment method. Only "mvola" is currently supported.');
        }

        $order = $this->orderService->findByReference($payment->getReference());
        if (!$order) {
            throw new NotFoundHttpException('Order not found with reference: ' . $payment->getReference());
        }

        $user = $order->getOwner();
        if (!$user) {
            throw new NotFoundHttpException('Order owner not found.');
        }

        $order->setPayment($payment);
        $this->orderService->updateOrder($order);

        try {
            $result = $this->paymentIntentService->createPaymentIntent($user, $payment);
           
            return new JsonResponse($result, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
