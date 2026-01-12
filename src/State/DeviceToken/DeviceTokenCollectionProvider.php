<?php

namespace App\State\DeviceToken;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\FcmTokenService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Provider for getting user's registered device tokens.
 */
readonly class DeviceTokenCollectionProvider implements ProviderInterface
{
    public function __construct(
        private FcmTokenService $fcmTokenService,
        private Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'You must be logged in');
        }

        $deviceTokens = $this->fcmTokenService->getUserDeviceTokens($user);

        return array_map(function ($token) {
            return [
                'id' => $token->getId(),
                'platform' => $token->getPlatform(),
                'deviceName' => $token->getDeviceName(),
                'appVersion' => $token->getAppVersion(),
                'isActive' => $token->isActive(),
                'lastUsedAt' => $token->getLastUsedAt()?->format('c'),
                'createdAt' => $token->getCreatedAt()?->format('c'),
            ];
        }, $deviceTokens);
    }
}
