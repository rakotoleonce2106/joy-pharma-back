<?php

namespace App\State\DeviceToken;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\UnregisterDeviceTokenInput;
use App\Service\FcmTokenService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Processor for unregistering FCM device tokens.
 */
readonly class UnregisterDeviceTokenProcessor implements ProcessorInterface
{
    public function __construct(
        private FcmTokenService $fcmTokenService,
        private Security $security
    ) {
    }

    /**
     * @param UnregisterDeviceTokenInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'You must be logged in to unregister a device token');
        }

        $success = $this->fcmTokenService->unregisterToken($data->fcmToken, $user);

        if (!$success) {
            throw new NotFoundHttpException('Device token not found or does not belong to you');
        }

        return [
            'success' => true,
            'message' => 'Device token unregistered successfully',
        ];
    }
}
