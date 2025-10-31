<?php

namespace App\State\Store;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\StoreSetting;
use App\Entity\User;
use App\Repository\StoreSettingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreSettingProvider implements ProviderInterface
{
    public function __construct(
        private readonly StoreSettingRepository $storeSettingRepository,
        private readonly Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?StoreSetting
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('User not authenticated');
        }

        // If id is not provided or invalid, get from user's store
        if (!$user->getStore()) {
            throw new NotFoundHttpException('User does not have an associated store');
        }

        $storeSetting = $user->getStore()->getSetting();

        if (!$storeSetting) {
            throw new NotFoundHttpException('Store setting not found for your store');
        }

        return $storeSetting;
    }
}

