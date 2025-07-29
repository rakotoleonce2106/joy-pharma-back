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


class CreatePaymentIntent extends AbstractController
{
    public function __construct(private readonly PaymentIntentService $paymentIntentService,
    private readonly OrderService $orderService
    ) {}

    public function __invoke(#[MapRequestPayload] Payment $payment): JsonResponse
    {
        
        $paymentMethod = $payment->getMethod();
        if ($paymentMethod !== PaymentMethod::METHODE_MVOLA) {
            throw new BadRequestHttpException('Invalid payment method. Supported methods are "stripe" and "mvola".');
        }
        $order = $this->orderService->findByReference($payment->getReference());
        $user = $order->getOwner();
         $order->setPayment($payment);

        try {
            $result = $this->paymentIntentService->createPaymentIntent($user, $payment);
           
            return new JsonResponse($result, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
