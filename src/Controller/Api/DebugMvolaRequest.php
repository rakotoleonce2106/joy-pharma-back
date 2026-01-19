<?php

namespace App\Controller\Api;

use App\Entity\Payment;
use App\Entity\PaymentMethod;
use App\Service\OrderService;
use DahRomy\MVola\Model\TransactionRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Debug endpoint to see what MVola request would be sent
 * REMOVE THIS IN PRODUCTION
 */
class DebugMvolaRequest extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly ParameterBagInterface $params
    ) {}

    #[Route('/api/debug-mvola-request', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload(serializationContext: ['groups' => ['payment:create']])] Payment $payment): JsonResponse
    {
        $paymentMethod = $payment->getMethod();
        if ($paymentMethod !== PaymentMethod::METHODE_MVOLA) {
            throw new BadRequestHttpException('This endpoint only works with MVola payments');
        }

        // Try to find order by the linked entity (IRI) and/or by reference string
        $order = $payment->getOrder();
        $reference = $payment->getReference();

        if (!$order && !$reference) {
            throw new BadRequestHttpException('Order IRI or Reference must be provided.');
        }

        if (!$order && $reference) {
            $order = $this->orderService->findByReference($reference);
            if (!$order) {
                throw new NotFoundHttpException(sprintf('Order with reference "%s" not found.', $reference));
            }
        }

        $user = $order->getOwner();
        if (!$user) {
            throw new NotFoundHttpException('Order owner not found.');
        }

        $amount = (int)$order->getTotalAmount();
        $currency = 'Ar';

        // Build the transaction request to show what would be sent
        $transactionRequest = new TransactionRequest();
        $transactionRequest->setAmount((string) $amount);
        $transactionRequest->setCurrency($currency);
        $transactionRequest->setDescriptionText("Subscription payment for {$user->getFullName()}");
        $transactionRequest->setRequestingOrganisationTransactionReference(uniqid('payment_intent', false));
        $transactionRequest->setRequestDate(new \DateTime());
        $transactionRequest->setOriginalTransactionReference($payment->getReference() ?? $order->getReference());
        $transactionRequest->setDebitParty([['key' => 'msisdn', 'value' => $payment->getPhoneNumber()]]);
        $transactionRequest->setCreditParty([['key' => 'msisdn', 'value' => $this->params->get('mvola.merchant_number')]]);
        $transactionRequest->setMetadata([
            ['key' => 'partnerName', 'value' => $this->params->get('mvola.company_name')],
            ['key' => 'fc', 'value' => $currency],
            ['key' => 'amountFc', 'value' => (string) $amount]
        ]);
        $transactionRequest->setCallbackData([
            'userId' => $user->getId(),
            'reference' => $payment->getReference() ?? $order->getReference()
        ]);

        return new JsonResponse([
            'debug' => true,
            'message' => 'This shows what would be sent to MVola API',
            'request_body' => $transactionRequest->toArray(),
            'config' => [
                'merchant_number' => $this->params->get('mvola.merchant_number'),
                'company_name' => $this->params->get('mvola.company_name'),
                'environment' => $this->params->get('mvola.environment'),
            ],
            'input' => [
                'phone_number' => $payment->getPhoneNumber(),
                'order_reference' => $order->getReference(),
                'amount' => $amount,
                'currency' => $currency,
                'user_name' => $user->getFullName(),
            ]
        ], Response::HTTP_OK);
    }
}
