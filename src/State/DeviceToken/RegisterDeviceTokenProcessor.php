<?php

namespace App\State\DeviceToken;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\RegisterDeviceTokenInput;
use App\Entity\DeviceToken;
use App\Service\FcmTokenService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Processor for registering FCM device tokens.
 */
readonly class RegisterDeviceTokenProcessor implements ProcessorInterface
{
    public function __construct(
        private FcmTokenService $fcmTokenService,
        private Security $security
    ) {
    }

    /**
     * @param RegisterDeviceTokenInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'You must be logged in to register a device token');
        }

        $deviceToken = $this->fcmTokenService->registerToken(
            $user,
            $data->fcmToken,
            $data->platform,
            $data->deviceName,
            $data->appVersion
        );

        return [
            'success' => true,
            'message' => 'Device token registered successfully',
            'deviceToken' => [
                'id' => $deviceToken->getId(),
                'platform' => $deviceToken->getPlatform(),
                'deviceName' => $deviceToken->getDeviceName(),
                'isActive' => $deviceToken->isActive(),
                'createdAt' => $deviceToken->getCreatedAt()?->format('c'),
            ],
        ];
    }
}
