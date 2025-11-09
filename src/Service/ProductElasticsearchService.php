<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class ProductElasticsearchService
{
    private const INDEX_NAME = 'products';

    public function __construct(
        private readonly ElasticsearchService $elasticsearchService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function initializeIndex(): void
    {
        $mapping = [
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'code' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'description' => [
                    'type' => 'text',
                    'analyzer' => 'standard'
                ],
                'brand' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'text', 'analyzer' => 'standard']
                    ]
                ],
                'manufacturer' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'text', 'analyzer' => 'standard']
                    ]
                ],
                'form' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'text', 'analyzer' => 'standard']
                    ]
                ],
                'categories' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'text', 'analyzer' => 'standard']
                    ]
                ],
                'isActive' => ['type' => 'boolean'],
                'unitPrice' => ['type' => 'float'],
                'totalPrice' => ['type' => 'float'],
                'quantity' => ['type' => 'integer'],
                'stock' => ['type' => 'integer'],
                'currency' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'label' => ['type' => 'keyword']
                    ]
                ],
                'createdAt' => ['type' => 'date'],
                'updatedAt' => ['type' => 'date']
            ]
        ];

        $this->elasticsearchService->createIndex(self::INDEX_NAME, $mapping);
    }

    public function indexProduct(Product $product): void
    {
        $document = $this->productToDocument($product);
        $this->elasticsearchService->indexDocument(self::INDEX_NAME, (string) $product->getId(), $document);
    }

    public function updateProduct(Product $product): void
    {
        $document = $this->productToDocument($product);
        // Use indexDocument instead of updateDocument to handle both create and update
        $this->elasticsearchService->indexDocument(self::INDEX_NAME, (string) $product->getId(), $document);
    }

    public function deleteProduct(int $productId): void
    {
        $this->elasticsearchService->deleteDocument(self::INDEX_NAME, (string) $productId);
    }

    public function searchProducts(string $query, array $filters = [], int $page = 1, int $limit = 10): array
    {
        $from = ($page - 1) * $limit;
        
        $searchQuery = [
            'from' => $from,
            'size' => $limit,
            'query' => [
                'bool' => [
                    'must' => []
                ]
            ]
        ];

        // Add text search
        if (!empty($query)) {
            $searchQuery['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['name^3', 'code^2', 'description', 'brand.name', 'manufacturer.name', 'form.name', 'categories.name'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO'
                ]
            ];
        } else {
            $searchQuery['query']['bool']['must'][] = ['match_all' => new \stdClass()];
        }

        // Add filters
        if (!empty($filters)) {
            if (isset($filters['category'])) {
                $searchQuery['query']['bool']['filter'][] = [
                    'nested' => [
                        'path' => 'categories',
                        'query' => [
                            'term' => ['categories.id' => (int) $filters['category']]
                        ]
                    ]
                ];
            }
            
            if (isset($filters['brand'])) {
                $searchQuery['query']['bool']['filter'][] = [
                    'nested' => [
                        'path' => 'brand',
                        'query' => [
                            'term' => ['brand.id' => (int) $filters['brand']]
                        ]
                    ]
                ];
            }
            
            if (isset($filters['manufacturer'])) {
                $searchQuery['query']['bool']['filter'][] = [
                    'nested' => [
                        'path' => 'manufacturer',
                        'query' => [
                            'term' => ['manufacturer.id' => (int) $filters['manufacturer']]
                        ]
                    ]
                ];
            }
            
            if (isset($filters['isActive'])) {
                $searchQuery['query']['bool']['filter'][] = [
                    'term' => ['isActive' => filter_var($filters['isActive'], FILTER_VALIDATE_BOOLEAN)]
                ];
            }
        }

        // Add sorting
        $searchQuery['sort'] = [
            ['_score' => ['order' => 'desc']],
            ['createdAt' => ['order' => 'desc']]
        ];

        $result = $this->elasticsearchService->search(self::INDEX_NAME, $searchQuery);
        
        // Extract product IDs from search results (preserve order)
        $productIds = [];
        $scores = [];
        foreach ($result['hits']['hits'] ?? [] as $hit) {
            $productIds[] = $hit['_source']['id'];
            $scores[$hit['_source']['id']] = $hit['_score'] ?? 0;
        }

        if (empty($productIds)) {
            return [];
        }

        // Fetch products from database
        $products = $this->entityManager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $productIds)
            ->getQuery()
            ->getResult();

        // Sort products by Elasticsearch score order
        usort($products, function ($a, $b) use ($scores) {
            $scoreA = $scores[$a->getId()] ?? 0;
            $scoreB = $scores[$b->getId()] ?? 0;
            return $scoreB <=> $scoreA;
        });

        return $products;
    }

    private function productToDocument(Product $product): array
    {
        $document = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'code' => $product->getCode(),
            'description' => $product->getDescription(),
            'isActive' => $product->isActive() ?? false,
            'unitPrice' => $product->getUnitPrice(),
            'totalPrice' => $product->getTotalPrice(),
            'quantity' => $product->getQuantity(),
            'stock' => $product->getStock(),
            'createdAt' => $product->getCreatedAt()?->format('c'),
            'updatedAt' => $product->getUpdatedAt()?->format('c')
        ];

        // Add brand
        if ($product->getBrand()) {
            $document['brand'] = [
                'id' => $product->getBrand()->getId(),
                'name' => $product->getBrand()->getName()
            ];
        }

        // Add manufacturer
        if ($product->getManufacturer()) {
            $document['manufacturer'] = [
                'id' => $product->getManufacturer()->getId(),
                'name' => $product->getManufacturer()->getName()
            ];
        }

        // Add form
        if ($product->getForm()) {
            $document['form'] = [
                'id' => $product->getForm()->getId(),
                'name' => $product->getForm()->getLabel()
            ];
        }

        // Add categories
        $document['categories'] = [];
        foreach ($product->getCategory() as $category) {
            $document['categories'][] = [
                'id' => $category->getId(),
                'name' => $category->getName()
            ];
        }

        // Add currency
        if ($product->getCurrency()) {
            $document['currency'] = [
                'id' => $product->getCurrency()->getId(),
                'label' => $product->getCurrency()->getLabel()
            ];
        }

        return $document;
    }

    public function reindexAll(): void
    {
        $this->initializeIndex();
        
        $products = $this->entityManager->getRepository(Product::class)->findAll();
        
        $operations = [];
        foreach ($products as $product) {
            $document = $this->productToDocument($product);
            $operations[] = [
                'index' => [
                    '_index' => $this->elasticsearchService->getIndexName(self::INDEX_NAME),
                    '_id' => (string) $product->getId()
                ]
            ];
            $operations[] = $document;
        }

        if (!empty($operations)) {
            $this->elasticsearchService->bulk($operations);
        }
    }
}

