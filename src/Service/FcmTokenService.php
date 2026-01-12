<?php

namespace App\Service;

use App\Entity\DeviceToken;
use App\Entity\User;
use App\Repository\DeviceTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing Firebase Cloud Messaging (FCM) device tokens.
 * Handles registration, updates, and cleanup of FCM tokens following best practices.
 */
readonly class FcmTokenService
{
    /**
     * Maximum number of failed attempts before a token is considered stale.
     */
    private const MAX_FAILED_ATTEMPTS = 3;

    /**
     * Number of days after which an unused token is considered inactive.
     */
    private const INACTIVE_DAYS_THRESHOLD = 90;

    public function __construct(
        private DeviceTokenRepository $deviceTokenRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Register or update an FCM token for a user.
     * If the token already exists for this user, it updates the metadata.
     * If the token exists for another user, it transfers ownership.
     *
     * @param User $user The user to register the token for
     * @param string $fcmToken The FCM registration token
     * @param string|null $platform The platform (ios, android, web)
     * @param string|null $deviceName Optional device name
     * @param string|null $appVersion Optional app version
     * @return DeviceToken The registered or updated device token
     */
    public function registerToken(
        User $user,
        string $fcmToken,
        ?string $platform = null,
        ?string $deviceName = null,
        ?string $appVersion = null
    ): DeviceToken {
        // Check if this token already exists
        $existingToken = $this->deviceTokenRepository->findByFcmToken($fcmToken);

        if ($existingToken !== null) {
            // If token belongs to a different user, transfer ownership
            if ($existingToken->getUser() !== $user) {
                $this->logger->info('Transferring FCM token to new user', [
                    'fcm_token' => substr($fcmToken, 0, 20) . '...',
                    'old_user_id' => $existingToken->getUser()?->getId(),
                    'new_user_id' => $user->getId(),
                ]);

                $existingToken->setUser($user);
            }

            // Update metadata
            $existingToken->setPlatform($platform);
            $existingToken->setDeviceName($deviceName);
            $existingToken->setAppVersion($appVersion);
            $existingToken->setIsActive(true);
            $existingToken->resetFailedAttempts();
            $existingToken->markAsUsed();

            $this->entityManager->flush();

            $this->logger->info('Updated existing FCM token', [
                'device_token_id' => $existingToken->getId(),
                'user_id' => $user->getId(),
            ]);

            return $existingToken;
        }

        // Create new token
        $deviceToken = new DeviceToken();
        $deviceToken->setUser($user);
        $deviceToken->setFcmToken($fcmToken);
        $deviceToken->setPlatform($platform);
        $deviceToken->setDeviceName($deviceName);
        $deviceToken->setAppVersion($appVersion);
        $deviceToken->markAsUsed();

        $this->deviceTokenRepository->save($deviceToken, true);

        $this->logger->info('Registered new FCM token', [
            'device_token_id' => $deviceToken->getId(),
            'user_id' => $user->getId(),
            'platform' => $platform,
        ]);

        return $deviceToken;
    }

    /**
     * Unregister an FCM token (e.g., on logout or device removal).
     *
     * @param string $fcmToken The FCM token to unregister
     * @param User|null $user Optional user to verify ownership
     * @return bool True if the token was removed
     */
    public function unregisterToken(string $fcmToken, ?User $user = null): bool
    {
        $deviceToken = $this->deviceTokenRepository->findByFcmToken($fcmToken);

        if ($deviceToken === null) {
            $this->logger->debug('FCM token not found for unregistration', [
                'fcm_token' => substr($fcmToken, 0, 20) . '...',
            ]);
            return false;
        }

        // If user is specified, verify ownership
        if ($user !== null && $deviceToken->getUser() !== $user) {
            $this->logger->warning('Attempted to unregister FCM token belonging to another user', [
                'fcm_token' => substr($fcmToken, 0, 20) . '...',
                'owner_id' => $deviceToken->getUser()?->getId(),
                'requester_id' => $user->getId(),
            ]);
            return false;
        }

        $this->deviceTokenRepository->remove($deviceToken, true);

        $this->logger->info('Unregistered FCM token', [
            'user_id' => $deviceToken->getUser()?->getId(),
        ]);

        return true;
    }

    /**
     * Deactivate an FCM token (sets isActive to false instead of deleting).
     */
    public function deactivateToken(string $fcmToken): bool
    {
        $deviceToken = $this->deviceTokenRepository->findByFcmToken($fcmToken);

        if ($deviceToken === null) {
            return false;
        }

        $deviceToken->setIsActive(false);
        $this->entityManager->flush();

        $this->logger->info('Deactivated FCM token', [
            'device_token_id' => $deviceToken->getId(),
        ]);

        return true;
    }

    /**
     * Get all active FCM tokens for a user.
     *
     * @return string[]
     */
    public function getUserTokens(User $user): array
    {
        return $this->deviceTokenRepository->findActiveFcmTokensByUser($user);
    }

    /**
     * Get all active device token entities for a user.
     *
     * @return DeviceToken[]
     */
    public function getUserDeviceTokens(User $user): array
    {
        return $this->deviceTokenRepository->findActiveTokensByUser($user);
    }

    /**
     * Get all FCM tokens for multiple users.
     *
     * @param User[] $users
     * @return string[]
     */
    public function getTokensForUsers(array $users): array
    {
        $tokens = [];
        foreach ($users as $user) {
            $tokens = array_merge($tokens, $this->getUserTokens($user));
        }
        return array_unique($tokens);
    }

    /**
     * Get all active FCM tokens (for broadcast notifications).
     *
     * @return string[]
     */
    public function getAllActiveTokens(): array
    {
        return $this->deviceTokenRepository->findAllActiveFcmTokens();
    }

    /**
     * Mark a token as successfully used.
     */
    public function markTokenAsUsed(string $fcmToken): void
    {
        $deviceToken = $this->deviceTokenRepository->findByFcmToken($fcmToken);

        if ($deviceToken !== null) {
            $deviceToken->markAsUsed();
            $this->entityManager->flush();
        }
    }

    /**
     * Handle a failed push notification attempt.
     * Increments the failed attempts counter and may deactivate the token.
     */
    public function handleFailedPush(string $fcmToken): void
    {
        $deviceToken = $this->deviceTokenRepository->findByFcmToken($fcmToken);

        if ($deviceToken === null) {
            return;
        }

        $deviceToken->incrementFailedAttempts();

        if ($deviceToken->getFailedAttempts() >= self::MAX_FAILED_ATTEMPTS) {
            $deviceToken->setIsActive(false);
            $this->logger->warning('FCM token deactivated due to failed attempts', [
                'device_token_id' => $deviceToken->getId(),
                'failed_attempts' => $deviceToken->getFailedAttempts(),
            ]);
        }

        $this->entityManager->flush();
    }

    /**
     * Cleanup stale and inactive tokens.
     * This should be run periodically (e.g., via a scheduled command).
     *
     * @return array Statistics about cleaned up tokens
     */
    public function cleanupTokens(): array
    {
        $staleRemoved = $this->deviceTokenRepository->removeStaleTokens(self::MAX_FAILED_ATTEMPTS);
        $inactiveRemoved = $this->deviceTokenRepository->removeInactiveTokens(self::INACTIVE_DAYS_THRESHOLD);

        $this->logger->info('Cleaned up FCM tokens', [
            'stale_removed' => $staleRemoved,
            'inactive_removed' => $inactiveRemoved,
        ]);

        return [
            'stale_removed' => $staleRemoved,
            'inactive_removed' => $inactiveRemoved,
            'total_removed' => $staleRemoved + $inactiveRemoved,
        ];
    }

    /**
     * Get token count statistics for a user.
     */
    public function getUserTokenStats(User $user): array
    {
        $tokens = $this->deviceTokenRepository->findTokensByUserGroupedByPlatform($user);

        $stats = [
            'total' => 0,
            'by_platform' => [],
        ];

        foreach ($tokens as $platform => $platformTokens) {
            $count = count($platformTokens);
            $stats['by_platform'][$platform] = $count;
            $stats['total'] += $count;
        }

        return $stats;
    }
}
