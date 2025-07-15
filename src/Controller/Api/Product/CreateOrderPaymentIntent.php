<?php

namespace App\Controller\Api\Product;

use App\Entity\Order;
use App\Entity\PaymentMethod;
use App\Entity\User;
use App\Service\PaymentIntentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class CreateOrderPaymentIntent extends AbstractController
{
    public function __construct(private readonly PaymentIntentService $paymentIntentService) {}

    public function __invoke(#[MapRequestPayload] Order $order): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new BadRequestHttpException('User not found or not authenticated.');
        }

        $paymentMethod = $order->getPayment()->getMethod();

        if ($paymentMethod !== PaymentMethod::METHODE_MVOLA) {
            throw new BadRequestHttpException('Invalid payment method. Supported methods are "stripe" and "mvola".');
        }

        try {
            $result = $this->paymentIntentService->createPaymentIntent($user, order: $order);

            return new JsonResponse($result, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
