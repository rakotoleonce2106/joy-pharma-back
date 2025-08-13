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
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $user = $this->security->getUser();

        if ($user instanceof User && $this->security->isGranted('ROLE_STORE')) {
            $store = $this->storeRepository->findOneBy(['owner' => $user]);

            if ($store) {
                $storeCategories = $store->getCategories();

                $parentIds = array_map(fn($cat) => $cat->getId(), $storeCategories->toArray());

                $qb = $this->categoryRepository->createQueryBuilder('c')
                    ->where('c.parent IN (:parentIds)')
                    ->setParameter('parentIds', $parentIds);

                $categories = $qb->getQuery()->getResult();

                return $categories;
            }
        }


        return $this->categoryRepository->findAll();
    }
}
