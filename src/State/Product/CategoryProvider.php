<?php

namespace App\State\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
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

        // Skip store-specific logic for admin users
        if ($user instanceof User && $this->security->isGranted('ROLE_STORE') && !$this->security->isGranted('ROLE_ADMIN')) {
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
        $filters = $context['filters'] ?? [];

        if (array_key_exists('parent', $filters)) {
            $parentValue = $filters['parent'];

            // Normaliser les valeurs vides, null, false, ou la chaîne "null" comme parent null
            if ($parentValue === false || 
                $parentValue === null || 
                $parentValue === '' || 
                $parentValue === 'null' ||
                (is_string($parentValue) && strtolower(trim($parentValue)) === 'null')) {
                return $this->categoryRepository->findBy(['parent' => null]);
            }

            // Parent avec ID spécifique
            if (is_numeric($parentValue)) {
                return $this->categoryRepository->findBy(['parent' => (int)$parentValue]);
            }

            // Valeur invalide
            throw new \InvalidArgumentException('Invalid parent filter value. Use a numeric ID or null.');
        }

        return $this->categoryRepository->findAll();
    }
}
