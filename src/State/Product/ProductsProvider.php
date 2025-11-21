<?php

namespace App\State\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ProductRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductsProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly RequestStack $requestStack
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $this->requestStack->getCurrentRequest();
        
        // Récupération des paramètres de pagination depuis les filtres ou directement depuis la requête
        $page = max(1, (int) ($context['filters']['page'] ?? $request?->query->get('page') ?? 1));
        $itemsPerPage = min(50, max(1, (int) ($context['filters']['itemsPerPage'] ?? $context['filters']['perPage'] ?? $request?->query->get('itemsPerPage') ?? $request?->query->get('perPage') ?? 20)));
        $offset = ($page - 1) * $itemsPerPage;

        // Récupération du filtre category depuis les filtres ou directement depuis la requête
        $categoryId = isset($context['filters']['category']) 
            ? (int) $context['filters']['category'] 
            : ($request?->query->get('category') ? (int) $request->query->get('category') : null);

        // Construction de la requête
        $qb = $this->productRepository->createQueryBuilder('p');

        // Filtre pour les produits actifs uniquement
        $qb->where('p.isActive = :isActive')
           ->setParameter('isActive', true);

        // Filtre par catégorie si fourni
        if ($categoryId !== null) {
            $qb->innerJoin('p.category', 'c')
               ->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId)
               ->groupBy('p.id'); // Nécessaire pour éviter les doublons avec ManyToMany
        }

        // Tri par défaut
        $qb->orderBy('p.id', 'ASC');

        // Application de la pagination
        $qb->setMaxResults($itemsPerPage)
           ->setFirstResult($offset);

        // Utilisation du Paginator pour que ApiPlatform puisse calculer les métadonnées de pagination
        return new Paginator($qb->getQuery());
    }
}

