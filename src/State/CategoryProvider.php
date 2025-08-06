<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Category;
use App\Entity\Store;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\StoreRepository;
use Symfony\Bundle\SecurityBundle\Security;

class CategoryProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private StoreRepository $storeRepository,
        private CategoryRepository $categoryRepository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $user = $this->security->getUser();
        
        if ($user instanceof User && $this->security->isGranted('ROLE_STORE')) {
            $store = $this->storeRepository->findOneBy(['owner' => $user]);
            
            if ($store) {
                $categories = $store->getCategories();
                
                if ($categories instanceof \Doctrine\Common\Collections\Collection) {
                    $categoryIds = [];
                    foreach ($categories as $category) {
                        $categoryIds[] = $category->getId();
                    }
                    return $this->categoryRepository->findBy(['id' => $categoryIds]);
                }

                return [];
            }
        }

        return $this->categoryRepository->findAll();
    }
}