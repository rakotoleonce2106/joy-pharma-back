<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\StoreSetting;
use App\Repository\StoreSettingRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provider for admin single store setting retrieval
 */
class AdminStoreSettingItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly StoreSettingRepository $storeSettingRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?StoreSetting
    {
        if (!isset($uriVariables['id'])) {
            throw new NotFoundHttpException('Store setting ID is required');
        }

        $storeSetting = $this->storeSettingRepository->find($uriVariables['id']);

        if (!$storeSetting) {
            throw new NotFoundHttpException('Store setting not found');
        }

        return $storeSetting;
    }
}
