<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Category;
use App\Entity\Store;
use App\Entity\StoreProduct;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\StoreProductRepository;
use App\Repository\StoreRepository;
use Symfony\Bundle\SecurityBundle\Security;

class StoreProductProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private StoreRepository $storeRepository,
        private StoreProductRepository $storeProductRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $user = $this->security->getUser();

        if ($user instanceof User && $this->security->isGranted('ROLE_STORE')) {
            $store = $this->storeRepository->findOneBy(['owner' => $user]);
            return $this->storeProductRepository->findAll(['store' => $store]);
        }
        
        return [];
    }
}
