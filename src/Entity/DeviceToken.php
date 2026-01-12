<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\DeviceTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Represents a device FCM token for push notifications.
 * One user can have multiple device tokens (one per device).
 * This follows Firebase best practices using FCM registration tokens instead of phone IDs.
 */
#[ORM\Entity(repositoryClass: DeviceTokenRepository::class)]
#[ORM\Table(name: '`device_token`')]
#[ORM\UniqueConstraint(name: 'unique_fcm_token', columns: ['fcm_token'])]
#[ORM\Index(name: 'idx_device_token_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_device_token_platform', columns: ['platform'])]
class DeviceToken
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'deviceTokens')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['device_token:read'])]
    private ?User $user = null;

    /**
     * The FCM registration token.
     * This is the unique token provided by Firebase Cloud Messaging SDK on the client device.
     */
    #[ORM\Column(length: 500)]
    #[Groups(['device_token:read', 'device_token:write'])]
    private ?string $fcmToken = null;

    /**
     * The platform/device type (ios, android, web).
     */
    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['device_token:read', 'device_token:write'])]
    private ?string $platform = null;

    /**
     * Optional device name for identification (e.g., "iPhone 15 Pro", "Pixel 8").
     */
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['device_token:read', 'device_token:write'])]
    private ?string $deviceName = null;

    /**
     * Optional app version for tracking.
     */
    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['device_token:read', 'device_token:write'])]
    private ?string $appVersion = null;

    /**
     * Whether push notifications are enabled for this device.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['device_token:read', 'device_token:write'])]
    private bool $isActive = true;

    /**
     * Last time a push notification was successfully sent to this token.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['device_token:read'])]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * Number of failed push attempts. Used for cleanup of stale tokens.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $failedAttempts = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(string $fcmToken): static
    {
        $this->fcmToken = $fcmToken;
        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): static
    {
        $this->platform = $platform;
        return $this;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName): static
    {
        $this->deviceName = $deviceName;
        return $this;
    }

    public function getAppVersion(): ?string
    {
        return $this->appVersion;
    }

    public function setAppVersion(?string $appVersion): static
    {
        $this->appVersion = $appVersion;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }

    public function setFailedAttempts(int $failedAttempts): static
    {
        $this->failedAttempts = $failedAttempts;
        return $this;
    }

    public function incrementFailedAttempts(): static
    {
        $this->failedAttempts++;
        return $this;
    }

    public function resetFailedAttempts(): static
    {
        $this->failedAttempts = 0;
        return $this;
    }

    /**
     * Marks the token as successfully used (updates lastUsedAt and resets failed attempts).
     */
    public function markAsUsed(): static
    {
        $this->lastUsedAt = new \DateTimeImmutable();
        $this->failedAttempts = 0;
        return $this;
    }
}
