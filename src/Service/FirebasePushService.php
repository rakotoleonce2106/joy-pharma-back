<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;

/**
 * Service for sending push notifications via Firebase Cloud Messaging through n8n.
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
 */
readonly class FirebasePushService
{
    /**
     * Maximum number of tokens per batch (FCM limit is 500 for multicast).
     */
    private const MAX_TOKENS_PER_BATCH = 500;

    public function __construct(
        private N8nService $n8nService,
        private FcmTokenService $fcmTokenService,
        private LoggerInterface $logger
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

        // Process tokens in batches
        $batches = array_chunk($tokens, self::MAX_TOKENS_PER_BATCH);

        foreach ($batches as $batch) {
            $batchResult = $this->sendBatch($batch, $title, $body, $data, $options);

            $results['success_count'] += $batchResult['success_count'] ?? count($batch);
            $results['failure_count'] += $batchResult['failure_count'] ?? 0;

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
        $result = $this->n8nService->triggerWebhook('push-notification', [
            'type' => 'single',
            'fcmToken' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->prepareDataPayload($data),
            'options' => $this->prepareOptions($options),
        ]);

        if ($result === null) {
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

        $result = $this->n8nService->triggerWebhook('push-notification', [
            'type' => 'data_only',
            'tokens' => $tokens,
            'data' => $this->prepareDataPayload($data),
            'options' => array_merge(['content_available' => true], $this->prepareOptions($options)),
        ]);

        return [
            'success' => $result !== null,
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
        $result = $this->n8nService->triggerWebhook('push-notification', [
            'type' => 'topic',
            'topic' => $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->prepareDataPayload($data),
            'options' => $this->prepareOptions($options),
        ]);

        $this->logger->info('Topic notification sent', [
            'topic' => $topic,
            'success' => $result !== null,
        ]);

        return $result !== null;
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
     * Send a batch of notifications via n8n.
     *
     * @param string[] $tokens Array of FCM tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Data payload
     * @param array $options FCM options
     * @return array Result from n8n
     */
    private function sendBatch(
        array $tokens,
        string $title,
        string $body,
        array $data,
        array $options
    ): array {
        $result = $this->n8nService->triggerWebhook('push-notification', [
            'type' => 'multicast',
            'tokens' => $tokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->prepareDataPayload($data),
            'options' => $this->prepareOptions($options),
        ]);

        if ($result === null) {
            return [
                'success_count' => 0,
                'failure_count' => count($tokens),
                'failed_tokens' => $tokens,
            ];
        }

        // Parse n8n response to get success/failure counts
        return [
            'success_count' => $result['success_count'] ?? count($tokens),
            'failure_count' => $result['failure_count'] ?? 0,
            'failed_tokens' => $result['failed_tokens'] ?? [],
        ];
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
}
