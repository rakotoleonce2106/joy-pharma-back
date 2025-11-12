<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\User;
use DahRomy\MVola\Model\TransactionRequest;
use Psr\Log\LoggerInterface;
use DahRomy\MVola\Service\MVolaService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class MvolaPaymentService
{

    public function __construct(
        private MVolaService          $mvolaService,
        private ParameterBagInterface $params,
        private LoggerInterface       $logger
    )
    {
    }

    public function createPaymentIntent(User $user, Payment $payment, int $amount, string $currency): array
    {
        $transactionRequest = $this->createTransactionRequest($user, $payment, $amount, $currency);

        try {
            $result = $this->mvolaService->initiateTransaction($transactionRequest);
            $this->logger->info('Mvola payment intent created', [
                'user_id' => $user->getId(),
                'transaction_id' => $result['serverCorrelationId'],
                'status' => $result['status']
            ]);

            return [
                'id' => $result['serverCorrelationId'],
                'client_secret' => $result['serverCorrelationId'],
                'status' => $result['status']
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create Mvola payment intent', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    private function createTransactionRequest(User $user, Payment $payment,string $amount,string $currency): TransactionRequest
    {
        $transactionRequest = new TransactionRequest();
        $transactionRequest->setAmount($amount);
        $transactionRequest->setCurrency($currency);
        $transactionRequest->setDescriptionText("Subscription payment for {$user->getFullName()}");
        $transactionRequest->setRequestingOrganisationTransactionReference(uniqid('payment_intent',false));
        $transactionRequest->setRequestDate(new \DateTime());
        $transactionRequest->setOriginalTransactionReference($payment->getReference());
        $transactionRequest->setDebitParty([['key' => 'msisdn', 'value' => $payment->getPhoneNumber()]]);
        $transactionRequest->setCreditParty([['key' => 'msisdn', 'value' => $this->params->get('mvola.merchant_number')]]);
        $transactionRequest->setMetadata([
            ['key' => 'partnerName', 'value' => $this->params->get('mvola.company_name')],
            ['key' => 'fc', 'value' => 'USD'],
            ['key' => 'amountFc', 'value' => '1']
        ]);
        $transactionRequest->setCallbackData([
            'userId' => $user->getId(),
            'reference' => $payment->getReference()
        ]);

        return $transactionRequest;
    }
}
