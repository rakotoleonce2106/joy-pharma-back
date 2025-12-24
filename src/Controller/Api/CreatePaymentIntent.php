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

    public function __invoke(#[MapRequestPayload(serializationContext: ['groups' => ['payment:create']])] Payment $payment): JsonResponse
    {
        $paymentMethod = $payment->getMethod();
        if (!in_array($paymentMethod, [PaymentMethod::METHODE_MVOLA, PaymentMethod::METHOD_MPGS])) {
            throw new BadRequestHttpException('Invalid payment method. Only "mvola" and "mpgs" are currently supported.');
        }

        // Try to find order by the linked entity (if passed via IRI) or by reference string
        $order = $payment->getOrder();
        if (!$order && $payment->getReference()) {
            $order = $this->orderService->findByReference($payment->getReference());
        }

        if (!$order) {
            throw new NotFoundHttpException('Order not found. Please provide a valid order IRI or reference.');
        }

        $user = $order->getOwner();
        if (!$user) {
            throw new NotFoundHttpException('Order owner not found.');
        }

        $payment->setOrder($order);
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
