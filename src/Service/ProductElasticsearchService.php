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
                        'keyword' => ['type' => 'keyword'],
                        'ngram' => [
                            'type' => 'text',
                            'analyzer' => 'ngram_analyzer'
                        ],
                        'edge_ngram' => [
                            'type' => 'text',
                            'analyzer' => 'edge_ngram_analyzer'
                        ]
                    ]
                ],
                'code' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => ['type' => 'keyword'],
                        'ngram' => [
                            'type' => 'text',
                            'analyzer' => 'ngram_analyzer'
                        ]
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
                'updatedAt' => ['type' => 'date'],
                // Champ vectoriel pour recherche KNN (optionnel, pour usage futur)
                // Nécessite Elasticsearch 8.0+ avec dense_vector
                // 'name_vector' => [
                //     'type' => 'dense_vector',
                //     'dims' => 384,
                //     'index' => true,
                //     'similarity' => 'cosine'
                // ]
            ]
        ];

        // Configuration des analyseurs pour meilleure recherche de similarité
        $settings = [
            'analysis' => [
                'analyzer' => [
                    'ngram_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'ngram_tokenizer',
                        'filter' => ['lowercase']
                    ],
                    'edge_ngram_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'edge_ngram_tokenizer',
                        'filter' => ['lowercase']
                    ]
                ],
                'tokenizer' => [
                    'ngram_tokenizer' => [
                        'type' => 'ngram',
                        'min_gram' => 2,
                        'max_gram' => 3,
                        'token_chars' => ['letter', 'digit']
                    ],
                    'edge_ngram_tokenizer' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 2,
                        'max_gram' => 10,
                        'token_chars' => ['letter', 'digit']
                    ]
                ]
            ]
        ];

        $this->elasticsearchService->createIndex(self::INDEX_NAME, $mapping, $settings);
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

    /**
     * Search for product title suggestions using Elasticsearch with KNN-like similarity
     * 
     * Cette méthode utilise plusieurs stratégies de recherche combinées pour obtenir
     * des résultats similaires à une recherche KNN (K-Nearest Neighbors) :
     * - N-gram et Edge N-gram pour la similarité de caractères
     * - Match phrase prefix pour l'autocomplétion
     * - Fuzzy matching pour les fautes de frappe
     * - Scoring pondéré pour prioriser les meilleurs résultats
     * 
     * @param string $query Search query (minimum 1 character)
     * @param int $limit Maximum number of suggestions to return (default: 10)
     * @return array Array of unique product titles sorted by relevance
     */
    public function searchTitleSuggestions(string $query, int $limit = 10): array
    {
        // Require minimum 1 character for suggestions
        $trimmedQuery = trim($query);
        if (strlen($trimmedQuery) < 1) {
            return [];
        }

        // Build a comprehensive search query using multiple strategies
        // This approach mimics KNN by finding the "nearest" matches based on various similarity metrics
        $searchQuery = [
            'size' => min($limit * 2, 50), // Get more results to ensure uniqueness
            '_source' => ['name', 'code'], // Return name and code fields
            'query' => [
                'bool' => [
                    'must' => [
                        ['term' => ['isActive' => true]] // Only active products
                    ],
                    'should' => [
                        // 1. Match phrase prefix - Best for autocomplete (highest boost)
                        // Trouve "Doliprane" quand on tape "Doli"
                        [
                            'match_phrase_prefix' => [
                                'name' => [
                                    'query' => $trimmedQuery,
                                    'boost' => 5.0,
                                    'max_expansions' => 50
                                ]
                            ]
                        ],
                        // 2. Edge N-gram - Excellent pour la recherche "as-you-type"
                        // Similaire au KNN en trouvant les termes qui commencent pareil
                        [
                            'match' => [
                                'name.edge_ngram' => [
                                    'query' => $trimmedQuery,
                                    'boost' => 4.0
                                ]
                            ]
                        ],
                        // 3. N-gram - Trouve des similarités de sous-chaînes
                        // Permet de trouver "paracétamol" même si on tape "acetamol"
                        [
                            'match' => [
                                'name.ngram' => [
                                    'query' => $trimmedQuery,
                                    'boost' => 3.0
                                ]
                            ]
                        ],
                        // 4. Match avec fuzzy - Tolère les fautes de frappe
                        // Similaire au KNN en trouvant les termes "proches"
                        [
                            'match' => [
                                'name' => [
                                    'query' => $trimmedQuery,
                                    'operator' => 'and',
                                    'boost' => 3.5,
                                    'fuzziness' => 'AUTO'
                                ]
                            ]
                        ],
                        // 5. Prefix sur keyword - Matching exact au début
                        [
                            'prefix' => [
                                'name.keyword' => [
                                    'value' => $trimmedQuery,
                                    'boost' => 4.5
                                ]
                            ]
                        ],
                        // 6. Match flexible avec OR pour casting plus large
                        [
                            'match' => [
                                'name' => [
                                    'query' => $trimmedQuery,
                                    'operator' => 'or',
                                    'boost' => 2.0
                                ]
                            ]
                        ],
                        // 7. Recherche sur le code produit
                        [
                            'match_phrase_prefix' => [
                                'code' => [
                                    'query' => $trimmedQuery,
                                    'boost' => 3.0
                                ]
                            ]
                        ],
                        [
                            'match' => [
                                'code.ngram' => [
                                    'query' => $trimmedQuery,
                                    'boost' => 2.5
                                ]
                            ]
                        ]
                    ],
                    'minimum_should_match' => 1
                ]
            ],
            'sort' => [
                '_score' => ['order' => 'desc'],
                'name.keyword' => ['order' => 'asc'] // Tri alphabétique en cas d'égalité
            ]
        ];

        try {
            $result = $this->elasticsearchService->search(self::INDEX_NAME, $searchQuery);
        } catch (\Exception $e) {
            // Log error and return empty array if Elasticsearch is unavailable
            error_log('Elasticsearch KNN-like suggestion search error: ' . $e->getMessage());
            return [];
        }

        // Extract unique product titles from search results
        $suggestions = [];
        $seenTitles = [];
        
        foreach ($result['hits']['hits'] ?? [] as $hit) {
            $title = $hit['_source']['name'] ?? null;
            
            if ($title && !in_array(strtolower($title), $seenTitles, true)) {
                $suggestions[] = $title;
                $seenTitles[] = strtolower($title);
                
                // Stop when we have enough unique suggestions
                if (count($suggestions) >= $limit) {
                    break;
                }
            }
        }

        return $suggestions;
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
            // Use should clause to combine multiple search strategies for better results
            // This ensures that "Doli" will find both "Doli" and "Doliprane"
            $searchQuery['query']['bool']['should'] = [
                // 1. Exact and fuzzy match (highest priority for exact matches)
                [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['name^5', 'code^4', 'brand.name', 'manufacturer.name', 'form.name', 'categories.name'],
                        'type' => 'best_fields',
                        'fuzziness' => 'AUTO',
                        'boost' => 3
                    ]
                ],
                // 2. Match phrase prefix - matches words that start with the query
                // This will match "Doli" with "Doliprane" because "Doli" is a prefix
                [
                    'match_phrase_prefix' => [
                        'name' => [
                            'query' => $query,
                            'boost' => 2.5,
                            'max_expansions' => 50
                        ]
                    ]
                ],
                [
                    'match_phrase_prefix' => [
                        'code' => [
                            'query' => $query,
                            'boost' => 2.5,
                            'max_expansions' => 50
                        ]
                    ]
                ],
                // 3. Simple match for partial word matching
                [
                    'match' => [
                        'name' => [
                            'query' => $query,
                            'operator' => 'and',
                            'boost' => 1.5
                        ]
                    ]
                ],
                [
                    'match' => [
                        'code' => [
                            'query' => $query,
                            'operator' => 'and',
                            'boost' => 1.5
                        ]
                    ]
                ]
            ];
            
            // At least one should clause must match
            $searchQuery['query']['bool']['minimum_should_match'] = 1;
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

        try {
            $result = $this->elasticsearchService->search(self::INDEX_NAME, $searchQuery);
        } catch (\Exception $e) {
            // Log error and return empty array if Elasticsearch is unavailable
            error_log('Elasticsearch search error: ' . $e->getMessage());
            return [];
        }
        
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

