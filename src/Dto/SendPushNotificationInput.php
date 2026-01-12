<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for sending push notifications (admin).
 */
class SendPushNotificationInput
{
    /**
     * Type of notification: single_user, multiple_users, topic, broadcast
     */
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['single_user', 'multiple_users', 'topic', 'broadcast'])]
    public string $type = 'single_user';

    /**
     * User ID for single_user type.
     */
    public ?int $userId = null;

    /**
     * User IDs for multiple_users type.
     * 
     * @var int[]
     */
    public array $userIds = [];

    /**
     * Topic name for topic type.
     */
    #[Assert\Length(max: 100)]
    public ?string $topic = null;

    /**
     * The notification title.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $title;

    /**
     * The notification body.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    public string $body;

    /**
     * Additional data payload.
     */
    public array $data = [];

    /**
     * Whether to also create an in-app notification.
     */
    public bool $createInAppNotification = true;
}
