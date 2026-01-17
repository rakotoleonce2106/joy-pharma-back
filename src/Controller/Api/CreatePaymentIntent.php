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
use Symfony\Component\Routing\Attribute\Route;


class CreatePaymentIntent extends AbstractController
{
    public function __construct(private readonly PaymentIntentService $paymentIntentService,
    private readonly OrderService $orderService
    ) {}

    #[Route('/api/create-payment-intent', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload(serializationContext: ['groups' => ['payment:create']])] Payment $payment): JsonResponse
    {
        $paymentMethod = $payment->getMethod();
        if (!in_array($paymentMethod, [PaymentMethod::METHODE_MVOLA, PaymentMethod::METHOD_MPGS])) {
            throw new BadRequestHttpException('Invalid payment method. Only "mvola" and "mpgs" are currently supported.');
        }

        // Try to find order by the linked entity (IRI) and/or by reference string
        $order = $payment->getOrder();
        $reference = $payment->getReference();

        if (!$order && !$reference) {
            throw new BadRequestHttpException('Order IRI or Reference must be provided.');
        }

        if ($order && $reference) {
            // Verify they match
            if ($order->getReference() !== $reference) {
                throw new BadRequestHttpException(sprintf(
                    'Reference mismatch: The provided reference "%s" does not match the reference of the provided order "%s".',
                    $reference,
                    $order->getReference()
                ));
            }
        } elseif (!$order && $reference) {
            // Find order by reference
            $order = $this->orderService->findByReference($reference);
            if (!$order) {
                throw new NotFoundHttpException(sprintf('Order with reference "%s" not found.', $reference));
            }
            $payment->setOrder($order);
        } elseif ($order && !$reference) {
            // Set reference from order
            $payment->setReference($order->getReference());
        }

        // Set amount from order details
        $payment->setAmount((string)$order->getTotalAmount());
        
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
