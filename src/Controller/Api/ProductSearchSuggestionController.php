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

    /**
     * Get search suggestions for product titles
     * 
     * Utilise Elasticsearch avec recherche KNN similarity pour retourner
     * des suggestions de titres pertinentes basées sur la requête de l'utilisateur.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/products/search/suggestions', name: 'api_products_search_suggestions', methods: ['GET'])]
    public function getSuggestions(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $limit = min(20, max(1, (int) $request->query->get('limit', 10)));
        $includeMetadata = filter_var(
            $request->query->get('metadata', 'false'),
            FILTER_VALIDATE_BOOLEAN
        );

        // Validate query - minimum 1 character
        if (empty($query)) {
            return $this->json([
                'suggestions' => [],
                'query' => $query,
                'count' => 0,
                'metadata' => [
                    'search_type' => 'empty',
                    'elapsed_time_ms' => 0
                ]
            ], Response::HTTP_OK);
        }

        // Record start time for performance metrics
        $startTime = microtime(true);

        // Get suggestions from Elasticsearch (uses KNN-like similarity scoring)
        $suggestions = $this->productElasticsearchService->searchTitleSuggestions($query, $limit);

        // Calculate elapsed time
        $elapsedTime = round((microtime(true) - $startTime) * 1000, 2);

        // Prepare response
        $response = [
            'suggestions' => $suggestions,
            'query' => $query,
            'count' => count($suggestions)
        ];

        // Add metadata if requested
        if ($includeMetadata) {
            $response['metadata'] = [
                'search_type' => 'knn_similarity',
                'elapsed_time_ms' => $elapsedTime,
                'limit' => $limit,
                'query_length' => strlen($query)
            ];
        }

        return $this->json($response, Response::HTTP_OK);
    }

    /**
     * Get detailed search suggestions with product information
     * 
     * Retourne des suggestions enrichies avec des informations détaillées
     * sur les produits (prix, stock, images, etc.)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/products/search/suggestions/detailed', name: 'api_products_search_suggestions_detailed', methods: ['GET'])]
    public function getDetailedSuggestions(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        // Validate query
        if (empty($query)) {
            return $this->json([
                'suggestions' => [],
                'query' => $query,
                'count' => 0
            ], Response::HTTP_OK);
        }

        // Record start time
        $startTime = microtime(true);

        // Get detailed suggestions (full product search with limited results)
        $products = $this->productElasticsearchService->searchProducts($query, [], 1, $limit);

        // Calculate elapsed time
        $elapsedTime = round((microtime(true) - $startTime) * 1000, 2);

        return $this->json([
            'suggestions' => $products,
            'query' => $query,
            'count' => count($products),
            'metadata' => [
                'search_type' => 'detailed_knn_similarity',
                'elapsed_time_ms' => $elapsedTime,
                'limit' => $limit
            ]
        ], Response::HTTP_OK, [], [
            'groups' => ['product:read', 'image:read', 'media_object:read', 'id:read']
        ]);
    }
}

