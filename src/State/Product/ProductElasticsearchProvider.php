<?php

namespace App\State\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\ProductElasticsearchService;

class ProductElasticsearchProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProductElasticsearchService $productElasticsearchService
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        // Extract search query from filters
        $query = $context['filters']['q'] ?? $context['filters']['query'] ?? '';
        $page = max(1, (int) ($context['filters']['page'] ?? 1));
        $limit = min(50, max(1, (int) ($context['filters']['perPage'] ?? $context['filters']['itemsPerPage'] ?? 10)));
        
        // Extract filters
        $filters = [];
        if (isset($context['filters']['category'])) {
            $filters['category'] = $context['filters']['category'];
        }
        if (isset($context['filters']['brand'])) {
            $filters['brand'] = $context['filters']['brand'];
        }
        if (isset($context['filters']['manufacturer'])) {
            $filters['manufacturer'] = $context['filters']['manufacturer'];
        }
        if (isset($context['filters']['isActive'])) {
            $filters['isActive'] = $context['filters']['isActive'];
        }

        // Perform Elasticsearch search
        return $this->productElasticsearchService->searchProducts($query, $filters, $page, $limit);
    }
}

