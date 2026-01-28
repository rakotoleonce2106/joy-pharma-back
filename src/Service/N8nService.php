<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service pour interagir avec n8n via webhooks et API
 */
readonly class N8nService
{
    private const DEFAULT_TIMEOUT = 30;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * Déclenche un webhook n8n avec les données fournies
     *
     * @param string $webhookPath Le chemin du webhook (ex: 'order-created', 'send-email')
     * @param array $data Les données à envoyer au webhook
     * @param array $options Options supplémentaires (headers, timeout, etc.)
     * @return array|null La réponse du webhook ou null en cas d'erreur
     */
    public function triggerWebhook(string $webhookPath, array $data, array $options = []): ?array
    {
        $baseUrl = $this->getWebhookBaseUrl();
        $url = rtrim($baseUrl, '/') . '/' . ltrim($webhookPath, '/');
        
        $timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
        $headers = $options['headers'] ?? [];
        
        try {
            $this->logger->info('Triggering n8n webhook', [
                'url' => $url,
                'webhook_path' => $webhookPath,
                'data_keys' => array_keys($data)
            ]);

            $response = $this->httpClient->request('POST', $url, [
                'json' => $data,
                'headers' => array_merge([
                    'Content-Type' => 'application/json',
                ], $headers),
                'timeout' => $timeout,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($statusCode >= 200 && $statusCode < 300) {
                $result = json_decode($content, true);
                $this->logger->info('n8n webhook triggered successfully', [
                    'webhook_path' => $webhookPath,
                    'status_code' => $statusCode
                ]);
                return $result;
            }

            $this->logger->warning('n8n webhook returned non-2xx status', [
                'webhook_path' => $webhookPath,
                'status_code' => $statusCode,
                'response' => $content
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logger->error('Error triggering n8n webhook', [
                'webhook_path' => $webhookPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Envoie une notification push via n8n
     *
     * @param string $fcmToken Le token FCM de l'utilisateur
     * @param string $title Le titre de la notification
     * @param string $body Le corps de la notification
     * @param array $data Données supplémentaires à envoyer
     * @return bool True si l'envoi a réussi
     */
    public function sendPushNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        $result = $this->triggerWebhook('push-notification', [
            'fcmToken' => $fcmToken,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        return $result !== null;
    }

    /**
     * Envoie un email via n8n
     *
     * @param string $to L'adresse email du destinataire
     * @param string $subject Le sujet de l'email
     * @param string $htmlBody Le corps HTML de l'email
     * @param string|null $textBody Le corps texte de l'email (optionnel)
     * @param array $attachments Liste des pièces jointes (optionnel)
     * @return bool True si l'envoi a réussi
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        array $attachments = []
    ): bool {
        $result = $this->triggerWebhook('send-email', [
            'to' => $to,
            'subject' => $subject,
            'htmlBody' => $htmlBody,
            'textBody' => $textBody,
            'attachments' => $attachments,
        ]);

        return $result !== null;
    }

    /**
     * Envoie un SMS via n8n (si configuré)
     *
     * @param string $phoneNumber Le numéro de téléphone
     * @param string $message Le message SMS
     * @return bool True si l'envoi a réussi
     */
    public function sendSMS(string $phoneNumber, string $message): bool
    {
        $result = $this->triggerWebhook('send-sms', [
            'phoneNumber' => $phoneNumber,
            'message' => $message,
        ]);

        return $result !== null;
    }

    /**
     * Déclenche un workflow n8n pour un événement spécifique
     *
     * @param string $eventType Le type d'événement (ex: 'order.created', 'user.registered')
     * @param array $payload Les données de l'événement
     * @return bool True si le déclenchement a réussi
     */
    public function triggerEvent(string $eventType, array $payload): bool
    {
        $result = $this->triggerWebhook('event', [
            'eventType' => $eventType,
            'payload' => $payload,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ]);

        return $result !== null;
    }

    /**
     * Récupère l'URL de base pour les webhooks n8n
     *
     * @return string
     */
    private function getWebhookBaseUrl(): string
    {
        $webhookUrl = $_ENV['N8N_WEBHOOK_URL'] ?? 'http://n8n:5678';
        $webhookType = $_ENV['N8N_WEBHOOK_TYPE'] ?? 'webhook';
        
        return rtrim($webhookUrl, '/') . '/' . ltrim($webhookType, '/');
    }
}

