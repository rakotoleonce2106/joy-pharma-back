<?php

namespace App\Repository;

use App\Entity\DeviceToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceToken>
 */
class DeviceTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceToken::class);
    }

    /**
     * Find a device token by its FCM token string.
     */
    public function findByFcmToken(string $fcmToken): ?DeviceToken
    {
        return $this->createQueryBuilder('dt')
            ->where('dt.fcmToken = :fcmToken')
            ->setParameter('fcmToken', $fcmToken)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all active tokens for a user.
     *
     * @return DeviceToken[]
     */
    public function findActiveTokensByUser(User $user): array
    {
        return $this->createQueryBuilder('dt')
            ->where('dt.user = :user')
            ->andWhere('dt.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('dt.lastUsedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all active FCM token strings for a user.
     *
     * @return string[]
     */
    public function findActiveFcmTokensByUser(User $user): array
    {
        $result = $this->createQueryBuilder('dt')
            ->select('dt.fcmToken')
            ->where('dt.user = :user')
            ->andWhere('dt.isActive = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return array_column($result, 'fcmToken');
    }

    /**
     * Get all active tokens for multiple users.
     *
     * @param User[] $users
     * @return DeviceToken[]
     */
    public function findActiveTokensByUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        return $this->createQueryBuilder('dt')
            ->where('dt.user IN (:users)')
            ->andWhere('dt.isActive = true')
            ->setParameter('users', $users)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all active FCM token strings (for broadcast notifications).
     *
     * @return string[]
     */
    public function findAllActiveFcmTokens(): array
    {
        $result = $this->createQueryBuilder('dt')
            ->select('dt.fcmToken')
            ->where('dt.isActive = true')
            ->getQuery()
            ->getResult();

        return array_column($result, 'fcmToken');
    }

    /**
     * Remove tokens that have exceeded the maximum failed attempts.
     */
    public function removeStaleTokens(int $maxFailedAttempts = 3): int
    {
        return $this->createQueryBuilder('dt')
            ->delete()
            ->where('dt.failedAttempts >= :maxAttempts')
            ->setParameter('maxAttempts', $maxFailedAttempts)
            ->getQuery()
            ->execute();
    }

    /**
     * Remove tokens that haven't been used in a specific number of days.
     */
    public function removeInactiveTokens(int $days = 90): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('dt')
            ->delete()
            ->where('dt.lastUsedAt IS NOT NULL')
            ->andWhere('dt.lastUsedAt < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Get tokens grouped by platform for a user.
     *
     * @return array<string, DeviceToken[]>
     */
    public function findTokensByUserGroupedByPlatform(User $user): array
    {
        $tokens = $this->findActiveTokensByUser($user);
        $grouped = [];

        foreach ($tokens as $token) {
            $platform = $token->getPlatform() ?? 'unknown';
            if (!isset($grouped[$platform])) {
                $grouped[$platform] = [];
            }
            $grouped[$platform][] = $token;
        }

        return $grouped;
    }

    /**
     * Save a device token entity.
     */
    public function save(DeviceToken $deviceToken, bool $flush = false): void
    {
        $this->getEntityManager()->persist($deviceToken);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a device token entity.
     */
    public function remove(DeviceToken $deviceToken, bool $flush = false): void
    {
        $this->getEntityManager()->remove($deviceToken);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
