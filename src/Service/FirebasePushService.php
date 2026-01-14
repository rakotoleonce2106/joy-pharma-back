<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for sending push notifications via Firebase Cloud Messaging HTTP v1 API.
 *
 * This service supports:
 * - Single device notifications using FCM token
 * - Multi-device notifications (one user, multiple devices)
 * - Batch notifications (multiple users)
 * - Topic-based notifications
 * - Data-only notifications (silent notifications)
 *
 * Firebase best practices implemented:
 * - Uses FCM registration tokens (not phone IDs)
 * - Supports multi-device per user
 * - Handles token refresh and cleanup
 * - Supports both notification + data payloads
 * - Uses FCM HTTP v1 API with OAuth2 authentication
 */
readonly class FirebasePushService
{
    /**
     * Maximum number of messages per batch (FCM v1 limit).
     */
    private const MAX_MESSAGES_PER_BATCH = 500;

    /**
     * FCM v1 API base URL
     */
    private const FCM_V1_BASE_URL = 'https://fcm.googleapis.com/v1/projects/';

    public function __construct(
        private HttpClientInterface $httpClient,
        private FcmTokenService $fcmTokenService,
        private LoggerInterface $logger,
        private ParameterBagInterface $params,
    ) {
    }

    /**
     * Send a push notification to a single user (all their devices).
     *
     * @param User $user The user to send notification to
     * @param string $title The notification title
     * @param string $body The notification body
     * @param array $data Additional data payload
     * @param array $options FCM options (priority, ttl, etc.)
     * @return array Result with success/failure counts
     */
    public function sendToUser(
        User $user,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        $tokens = $this->fcmTokenService->getUserTokens($user);

        if (empty($tokens)) {
            $this->logger->debug('No FCM tokens found for user', ['user_id' => $user->getId()]);
            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 0,
                'message' => 'No FCM tokens registered for user',
            ];
        }

        return $this->sendToTokens($tokens, $title, $body, $data, $options);
    }

    /**
     * Send a push notification to multiple users.
     *
     * @param User[] $users Array of users
     * @param string $title The notification title
     * @param string $body The notification body
     * @param array $data Additional data payload
     * @param array $options FCM options
     * @return array Result with success/failure counts
     */
    public function sendToUsers(
        array $users,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        $tokens = $this->fcmTokenService->getTokensForUsers($users);

        if (empty($tokens)) {
            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 0,
                'message' => 'No FCM tokens found for any of the users',
            ];
        }

        return $this->sendToTokens($tokens, $title, $body, $data, $options);
    }

    /**
     * Send a push notification to specific FCM tokens.
     *
     * @param string[] $tokens Array of FCM tokens
     * @param string $title The notification title
     * @param string $body The notification body
     * @param array $data Additional data payload
     * @param array $options FCM options
     * @return array Result with success/failure counts
     */
    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        if (empty($tokens)) {
            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 0,
                'message' => 'No tokens provided',
            ];
        }

        $results = [
            'success' => true,
            'success_count' => 0,
            'failure_count' => 0,
            'failed_tokens' => [],
        ];

        // Process tokens in batches (v1 API has individual message limits)
        $batches = array_chunk($tokens, self::MAX_MESSAGES_PER_BATCH);

        foreach ($batches as $batch) {
            $batchResult = $this->sendBatch($batch, $title, $body, $data, $options);

            $results['success_count'] += $batchResult['success_count'];
            $results['failure_count'] += $batchResult['failure_count'];

            if (!empty($batchResult['failed_tokens'])) {
                $results['failed_tokens'] = array_merge(
                    $results['failed_tokens'],
                    $batchResult['failed_tokens']
                );
            }
        }

        // Handle failed tokens
        foreach ($results['failed_tokens'] as $failedToken) {
            $this->fcmTokenService->handleFailedPush($failedToken);
        }

        $results['success'] = $results['failure_count'] < count($tokens);

        $this->logger->info('Push notification batch sent', [
            'total_tokens' => count($tokens),
            'success_count' => $results['success_count'],
            'failure_count' => $results['failure_count'],
        ]);

        return $results;
    }

    /**
     * Send a push notification to a single FCM token.
     *
     * @param string $fcmToken The FCM token
     * @param string $title The notification title
     * @param string $body The notification body
     * @param array $data Additional data payload
     * @param array $options FCM options
     * @return bool True if successful
     */
    public function sendToToken(
        string $fcmToken,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): bool {
        $message = [
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->prepareDataPayload($data),
        ];

        $preparedOptions = $this->prepareOptions($options);
        if (!empty($preparedOptions['android'])) {
            $message['android'] = $preparedOptions['android'];
        }
        if (!empty($preparedOptions['apns'])) {
            $message['apns'] = $preparedOptions['apns'];
        }

        $result = $this->sendFcmRequest($message);

        if (!$result['success']) {
            $this->fcmTokenService->handleFailedPush($fcmToken);
            return false;
        }

        $this->fcmTokenService->markTokenAsUsed($fcmToken);
        return true;
    }

    /**
     * Send a data-only (silent) notification.
     * These notifications don't show a visual alert but wake up the app to process data.
     *
     * @param User $user The user to send to
     * @param array $data The data payload
     * @param array $options FCM options
     * @return array Result
     */
    public function sendDataOnly(
        User $user,
        array $data,
        array $options = []
    ): array {
        $tokens = $this->fcmTokenService->getUserTokens($user);

        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'No FCM tokens found for user',
            ];
        }

        $preparedData = $this->prepareDataPayload($data);
        $options = array_merge(['content_available' => true], $this->prepareOptions($options));

        $batchResult = $this->sendBatch(
            $tokens,
            '',
            '',
            $preparedData,
            $options,
            dataOnly: true
        );

        return [
            'success' => $batchResult['failure_count'] < $batchResult['success_count'] + $batchResult['failure_count'],
            'tokens_count' => count($tokens),
        ];
    }

    /**
     * Send a notification to a FCM topic.
     * Topics allow you to send messages to multiple devices that have subscribed to a particular topic.
     *
     * @param string $topic The topic name
     * @param string $title The notification title
     * @param string $body The notification body
     * @param array $data Additional data payload
     * @param array $options FCM options
     * @return bool True if successful
     */
    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): bool {
        $message = [
            'topic' => $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->prepareDataPayload($data),
        ];

        $preparedOptions = $this->prepareOptions($options);
        if (!empty($preparedOptions['android'])) {
            $message['android'] = $preparedOptions['android'];
        }
        if (!empty($preparedOptions['apns'])) {
            $message['apns'] = $preparedOptions['apns'];
        }

        $result = $this->sendFcmRequest($message);

        $this->logger->info('Topic notification sent', [
            'topic' => $topic,
            'success' => $result['success'],
        ]);

        return $result['success'];
    }

    /**
     * Send a broadcast notification to all registered devices.
     *
     * @param string $title The notification title
     * @param string $body The notification body
     * @param array $data Additional data payload
     * @param array $options FCM options
     * @return array Result with success/failure counts
     */
    public function sendBroadcast(
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        $tokens = $this->fcmTokenService->getAllActiveTokens();

        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'No active FCM tokens found',
            ];
        }

        $this->logger->info('Sending broadcast notification', [
            'total_tokens' => count($tokens),
        ]);

        return $this->sendToTokens($tokens, $title, $body, $data, $options);
    }

    /**
     * Send a batch of notifications to multiple tokens.
     *
     * @param string[] $tokens Array of FCM tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Data payload
     * @param array $options FCM options
     * @return array Result with success/failure counts
     */
    private function sendBatch(
        array $tokens,
        string $title,
        string $body,
        array $data,
        array $options,
        bool $dataOnly = false,
    ): array {
        $results = [
            'success_count' => 0,
            'failure_count' => 0,
            'failed_tokens' => [],
        ];

        foreach ($tokens as $token) {
            $message = [
                'token' => $token,
                'data' => $this->prepareDataPayload($data),
            ];

            if (!$dataOnly) {
                $message['notification'] = [
                    'title' => $title,
                    'body' => $body,
                ];
            }

            $preparedOptions = $this->prepareOptions($options);
            if (!empty($preparedOptions['android'])) {
                $message['android'] = $preparedOptions['android'];
            }
            if (!empty($preparedOptions['apns'])) {
                $message['apns'] = $preparedOptions['apns'];
            }

            $result = $this->sendFcmRequest($message);

            if ($result['success']) {
                $results['success_count']++;
            } else {
                $results['failure_count']++;
                $results['failed_tokens'][] = $token;
            }
        }

        return $results;
    }

    /**
     * Prepare data payload ensuring all values are strings (FCM requirement).
     */
    private function prepareDataPayload(array $data): array
    {
        $prepared = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $prepared[$key] = json_encode($value);
            } elseif (is_bool($value)) {
                $prepared[$key] = $value ? 'true' : 'false';
            } else {
                $prepared[$key] = (string) $value;
            }
        }
        return $prepared;
    }

    /**
     * Prepare FCM options with defaults.
     */
    private function prepareOptions(array $options): array
    {
        return array_merge([
            'priority' => 'high',
            'ttl' => 86400, // 24 hours
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'channel_id' => 'joy_pharma_notifications',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ],
        ], $options);
    }

    /**
     * Send a raw request to Firebase Cloud Messaging using HTTP v1 API.
     *
     * @param array $message The FCM v1 message payload
     * @return array{
     *     success: bool,
     *     success_count?: int,
     *     failure_count?: int,
     *     failed_tokens?: array<int, string>
     * }
     */
    private function sendFcmRequest(array $message): array
    {
        $projectId = $this->params->get('env(FIREBASE_PROJECT_ID)');
        $accessToken = $this->getAccessToken();

        if (!$projectId || !$accessToken) {
            $this->logger->error('Firebase credentials not configured', [
                'has_project_id' => !empty($projectId),
                'has_access_token' => !empty($accessToken),
            ]);
            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 1,
                'failed_tokens' => [],
            ];
        }

        try {
            $url = self::FCM_V1_BASE_URL . $projectId . '/messages:send';

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['message' => $message],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
            $data = json_decode($content, true) ?? [];

            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->error('FCM v1 request failed', [
                    'status_code' => $statusCode,
                    'response' => $content,
                    'message_token' => $message['token'] ?? 'batch',
                ]);

                return [
                    'success' => false,
                    'success_count' => 0,
                    'failure_count' => 1,
                    'failed_tokens' => !empty($message['token']) ? [$message['token']] : [],
                ];
            }

            // FCM v1 API returns different response structure
            if (isset($data['name'])) {
                // Success - message was sent
                return [
                    'success' => true,
                    'success_count' => 1,
                    'failure_count' => 0,
                    'failed_tokens' => [],
                ];
            }

            // Handle error responses
            $error = $data['error'] ?? null;
            if ($error) {
                $this->logger->error('FCM v1 API error', [
                    'error' => $error,
                    'message_token' => $message['token'] ?? 'unknown',
                ]);

                return [
                    'success' => false,
                    'success_count' => 0,
                    'failure_count' => 1,
                    'failed_tokens' => !empty($message['token']) ? [$message['token']] : [],
                ];
            }

            return [
                'success' => true,
                'success_count' => 1,
                'failure_count' => 0,
                'failed_tokens' => [],
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Error sending FCM v1 request', [
                'error' => $e->getMessage(),
                'message_token' => $message['token'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 1,
                'failed_tokens' => !empty($message['token']) ? [$message['token']] : [],
            ];
        }
    }

    /**
     * Generate OAuth2 access token for Firebase using service account credentials.
     *
     * @return string|null The access token or null if generation failed
     */
    private function getAccessToken(): ?string
    {
        $clientEmail = $this->params->get('env(FIREBASE_CLIENT_EMAIL)');
        $privateKey = $this->params->get('env(FIREBASE_PRIVATE_KEY)');

        if (!$clientEmail || !$privateKey) {
            $this->logger->error('Firebase service account credentials not configured');
            return null;
        }

        try {
            // Create JWT payload
            $now = time();
            $jwtPayload = [
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600, // 1 hour
                'iat' => $now,
            ];

            // Create JWT header
            $jwtHeader = [
                'alg' => 'RS256',
                'typ' => 'JWT',
            ];

            // Encode header and payload
            $headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($jwtHeader)));
            $payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($jwtPayload)));

            // Create signature
            $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
            $signature = '';
            openssl_sign($headerEncoded . "." . $payloadEncoded, $signature, $privateKey, 'sha256WithRSAEncryption');
            $signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            $jwt = $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;

            // Exchange JWT for access token
            $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ],
            ]);

            $data = json_decode($response->getContent(), true);

            if (isset($data['access_token'])) {
                return $data['access_token'];
            }

            $this->logger->error('Failed to obtain access token', ['response' => $data]);
            return null;

        } catch (\Throwable $e) {
            $this->logger->error('Error generating Firebase access token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
