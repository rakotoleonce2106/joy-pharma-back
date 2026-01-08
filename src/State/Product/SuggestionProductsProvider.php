<?php

namespace App\State\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ProductRepository;
use Symfony\Bundle\SecurityBundle\Security;

class SuggestionProductsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private ProductRepository $productRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        // Extract pagination parameters from context
        $page = max(1, (int) ($context['filters']['page'] ?? 1));
        $limit = min(50, max(1, (int) ($context['filters']['itemsPerPage'] ?? $context['filters']['perPage'] ?? 10)));

        // Return recent active products (nouveautés) for suggestions
        return $this->productRepository->findRecentProducts($limit);
    }
}
