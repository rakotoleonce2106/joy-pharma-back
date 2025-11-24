<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class MPGSPaymentService
{
    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient
    ) {
    }

    public function createPaymentIntent(User $user, Payment $payment, int $amount, string $currency): array
    {
        try {
            $orderId = $payment->getTransactionId() ?? bin2hex(random_bytes(8));
            $checkoutSessionUrl = $this->buildCheckoutSessionUrl();
            $requestBody = $this->createCheckoutSessionRequest($orderId, $amount, $currency);

            $this->logger->info('Creating MPGS payment intent', [
                'user_id' => $user->getId(),
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
                'checkout_session_url' => $checkoutSessionUrl
            ]);

            $response = $this->sendTransaction($checkoutSessionUrl, $requestBody, 'POST');
            $responseData = json_decode($response, true);

            if (!isset($responseData['result']) || $responseData['result'] !== 'SUCCESS') {
                $errorMessage = $responseData['error']['explanation'] ?? 'Unknown error from MPGS gateway';
                throw new \Exception('MPGS payment intent creation failed: ' . $errorMessage);
            }

            $sessionId = $responseData['session']['id'] ?? null;
            $sessionVersion = $responseData['session']['version'] ?? null;
            $successIndicator = $responseData['successIndicator'] ?? null;

            if (!$sessionId) {
                throw new \Exception('MPGS checkout session creation failed: No session ID returned');
            }

            $this->logger->info('MPGS payment intent created', [
                'user_id' => $user->getId(),
                'order_id' => $orderId,
                'session_id' => $sessionId,
                'session_version' => $sessionVersion
            ]);

            return [
                'id' => $sessionId,
                'client_secret' => $sessionId,
                'status' => 'pending',
                'order_id' => $orderId,
                'session_id' => $sessionId,
                'session_version' => $sessionVersion,
                'success_indicator' => $successIndicator
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create MPGS payment intent', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createCheckoutSessionRequest(string $orderId, int $amount, string $currency): array
    {
        // Convert amount from cents to decimal format for MPGS
        $amountDecimal = number_format($amount / 100, 2, '.', '');

        $requestData = [
            'apiOperation' => 'CREATE_CHECKOUT_SESSION',
            'order' => [
                'id' => $orderId,
                'currency' => $currency
            ]
        ];

        return $requestData;
    }

    private function buildCheckoutSessionUrl(): string
    {
        $baseUrl = $this->params->get('mpgs.gateway_base_url');
        $version = $this->params->get('mpgs.api_version', '45');
        $merchantId = $this->params->get('mpgs.merchant_id');

        return $baseUrl . '/api/rest/version/' . $version . '/merchant/' . $merchantId . '/session';
    }

    private function buildGatewayUrl(?string $orderId = null): string
    {
        $baseUrl = $this->params->get('mpgs.gateway_base_url');
        $version = $this->params->get('mpgs.api_version', '45');
        $merchantId = $this->params->get('mpgs.merchant_id');

        $url = $baseUrl . '/api/rest/version/' . $version . '/merchant/' . $merchantId;

        if ($orderId) {
            $url .= '/order/' . $orderId;
        }

        return $url;
    }

    private function sendTransaction(string $gatewayUrl, array $requestBody, string $method = 'PUT'): string
    {
        $jsonRequest = json_encode($requestBody, JSON_PRETTY_PRINT);
        $merchantId = $this->params->get('mpgs.merchant_id');
        $apiPassword = $this->params->get('mpgs.api_password');
        $apiUsername = 'merchant.' . $merchantId;

        $authString = base64_encode($apiUsername . ':' . $apiPassword);

        $this->logger->debug('Sending MPGS transaction', [
            'url' => $gatewayUrl,
            'request' => $jsonRequest
        ]);

        try {
            $response = $this->httpClient->request($method, $gatewayUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . $authString
                ],
                'body' => $jsonRequest
            ]);

            $responseContent = $response->getContent();

            $this->logger->debug('MPGS transaction response', [
                'status_code' => $response->getStatusCode(),
                'response' => $responseContent
            ]);

            return $responseContent;
        } catch (\Exception $e) {
            $this->logger->error('MPGS HTTP request failed', [
                'error' => $e->getMessage(),
                'url' => $gatewayUrl
            ]);
            throw new \Exception('Failed to communicate with MPGS gateway: ' . $e->getMessage(), 0, $e);
        }
    }

    private function mapStatus(string $result): string
    {
        return match(strtoupper($result)) {
            'SUCCESS' => 'pending',
            'PENDING' => 'pending',
            'FAILURE', 'ERROR' => 'failed',
            default => 'pending'
        };
    }
}

