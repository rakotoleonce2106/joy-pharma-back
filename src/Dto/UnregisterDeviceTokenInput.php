<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for unregistering an FCM device token.
 */
class UnregisterDeviceTokenInput
{
    /**
     * The FCM registration token to unregister.
     */
    #[Assert\NotBlank(message: 'FCM token is required')]
    public string $fcmToken;
}
