<?php

namespace App\Controller\Api;

use App\Service\ProductElasticsearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductSearchSuggestionController extends AbstractController
{
    public function __construct(
        private readonly ProductElasticsearchService $productElasticsearchService
    ) {
    }

    #[Route('/api/products/search/suggestions', name: 'api_products_search_suggestions', methods: ['GET'])]
    public function getSuggestions(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $limit = min(20, max(1, (int) $request->query->get('limit', 10)));

        // Validate query
        if (empty($query)) {
            return $this->json([
                'suggestions' => [],
                'query' => $query,
                'count' => 0
            ], Response::HTTP_OK);
        }

        // Get suggestions from Elasticsearch
        $suggestions = $this->productElasticsearchService->searchTitleSuggestions($query, $limit);

        return $this->json([
            'suggestions' => $suggestions,
            'query' => $query,
            'count' => count($suggestions)
        ], Response::HTTP_OK);
    }
}

