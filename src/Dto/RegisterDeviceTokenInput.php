<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for registering an FCM device token.
 */
class RegisterDeviceTokenInput
{
    /**
     * The FCM registration token from Firebase Cloud Messaging.
     */
    #[Assert\NotBlank(message: 'FCM token is required')]
    #[Assert\Length(
        min: 100,
        max: 500,
        minMessage: 'FCM token seems too short',
        maxMessage: 'FCM token is too long'
    )]
    public string $fcmToken;

    /**
     * The platform/device type (ios, android, web).
     */
    #[Assert\Choice(
        choices: ['ios', 'android', 'web'],
        message: 'Platform must be one of: ios, android, web'
    )]
    public ?string $platform = null;

    /**
     * Optional device name for identification.
     */
    #[Assert\Length(max: 100)]
    public ?string $deviceName = null;

    /**
     * Optional app version.
     */
    #[Assert\Length(max: 20)]
    public ?string $appVersion = null;
}
