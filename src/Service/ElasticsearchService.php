<?php

namespace App\Service;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;

class ElasticsearchService
{
    private Client $client;
    private string $indexPrefix;
    private ?LoggerInterface $logger;
    private bool $isAvailable = false;
    private array $hosts;

    public function __construct(
        array $hosts,
        string $indexPrefix,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger;
        
        // Handle defaults - read from environment variables using getenv() which works better in Symfony
        $elasticsearchHost = getenv('ELASTICSEARCH_HOST') 
            ?: ($_ENV['ELASTICSEARCH_HOST'] ?? null)
            ?: ($_SERVER['ELASTICSEARCH_HOST'] ?? null);
        
        // Check if Elasticsearch is disabled
        $elasticsearchEnabled = getenv('ELASTICSEARCH_ENABLED') 
            ?: ($_ENV['ELASTICSEARCH_ENABLED'] ?? null)
            ?: ($_SERVER['ELASTICSEARCH_ENABLED'] ?? 'true');
        
        if (strtolower($elasticsearchEnabled) === 'false' || $elasticsearchEnabled === '0') {
            $this->isAvailable = false;
            $this->logger?->warning('Elasticsearch is disabled via ELASTICSEARCH_ENABLED environment variable');
            // Create a dummy client to avoid errors
            $this->client = ClientBuilder::create()->setHosts(['http://localhost:9200'])->build();
            $this->indexPrefix = $indexPrefix ?: 'joy_pharma';
            return;
        }
        
        if (empty($hosts) || (count($hosts) === 1 && (empty($hosts[0]) || $hosts[0] === ''))) {
            if ($elasticsearchHost) {
                $hosts = [$elasticsearchHost];
            } else {
                // Default to Lando/Docker service name if available, otherwise localhost
                $hosts = ['http://elasticsearch:9200'];
            }
        }
        
        // Filter out empty values
        $hosts = array_filter($hosts, fn($host) => !empty($host) && $host !== '');
        if (empty($hosts)) {
            $hosts = [$elasticsearchHost ?: 'http://elasticsearch:9200'];
        }
        
        $this->hosts = $hosts;
        
        $elasticsearchPrefix = getenv('ELASTICSEARCH_INDEX_PREFIX')
            ?: ($_ENV['ELASTICSEARCH_INDEX_PREFIX'] ?? null)
            ?: ($_SERVER['ELASTICSEARCH_INDEX_PREFIX'] ?? null);
        $this->indexPrefix = $indexPrefix ?: ($elasticsearchPrefix ?: 'joy_pharma');
        
        try {
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->setLogger($logger)
            ->build();
            
            // Test connection
            $this->checkAvailability();
        } catch (\Exception $e) {
            $this->logger?->error('Failed to initialize Elasticsearch client', [
                'hosts' => $hosts,
                'error' => $e->getMessage()
            ]);
            $this->isAvailable = false;
            // Create a dummy client to avoid errors
            $this->client = ClientBuilder::create()->setHosts($hosts)->build();
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getIndexPrefix(): string
    {
        return $this->indexPrefix;
    }

    public function getIndexName(string $index): string
    {
        return $this->indexPrefix . '_' . $index;
    }

    /**
     * Check if Elasticsearch is available and accessible
     */
    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    /**
     * Check Elasticsearch availability by pinging the cluster
     */
    public function checkAvailability(): bool
    {
        try {
            $response = $this->client->ping();
            $this->isAvailable = $response->asBool();
            
            if (!$this->isAvailable) {
                $this->logger?->warning('Elasticsearch ping returned false', ['hosts' => $this->hosts]);
            }
            
            return $this->isAvailable;
        } catch (\Exception $e) {
            $this->isAvailable = false;
            $this->logger?->error('Elasticsearch availability check failed', [
                'hosts' => $this->hosts,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function indexExists(string $index): bool
    {
        if (!$this->isAvailable) {
            return false;
        }
        
        try {
            $response = $this->client->indices()->exists(['index' => $this->getIndexName($index)]);
            return $response->asBool();
        } catch (\Exception $e) {
            $this->logger?->warning('Elasticsearch indexExists failed', [
                'index' => $this->getIndexName($index),
                'error' => $e->getMessage()
            ]);
            // If there's an exception (e.g., index doesn't exist or connection failed), return false
            return false;
        }
    }

    public function createIndex(string $index, array $mapping, array $settings = []): void
    {
        if (!$this->isAvailable) {
            $this->logger?->warning('Cannot create Elasticsearch index: service unavailable', [
                'index' => $this->getIndexName($index)
            ]);
            return;
        }
        
        $indexName = $this->getIndexName($index);
        
        if ($this->indexExists($index)) {
            return;
        }

        try {
        $body = ['mappings' => $mapping];
        
        if (!empty($settings)) {
            $body['settings'] = $settings;
        }

        $this->client->indices()->create([
            'index' => $indexName,
            'body' => $body
        ]);
        } catch (\Exception $e) {
            $this->logger?->error('Failed to create Elasticsearch index', [
                'index' => $indexName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function indexDocument(string $index, string $id, array $document): void
    {
        if (!$this->isAvailable) {
            $this->logger?->debug('Cannot index document: Elasticsearch unavailable', [
                'index' => $this->getIndexName($index),
                'id' => $id
            ]);
            return;
        }
        
        try {
        $this->client->index([
            'index' => $this->getIndexName($index),
            'id' => $id,
            'body' => $document
        ]);
        } catch (\Exception $e) {
            $this->logger?->error('Failed to index document in Elasticsearch', [
                'index' => $this->getIndexName($index),
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateDocument(string $index, string $id, array $document): void
    {
        if (!$this->isAvailable) {
            $this->logger?->debug('Cannot update document: Elasticsearch unavailable', [
                'index' => $this->getIndexName($index),
                'id' => $id
            ]);
            return;
        }
        
        try {
        $this->client->update([
            'index' => $this->getIndexName($index),
            'id' => $id,
            'body' => [
                'doc' => $document
            ]
        ]);
        } catch (\Exception $e) {
            $this->logger?->error('Failed to update document in Elasticsearch', [
                'index' => $this->getIndexName($index),
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteDocument(string $index, string $id): void
    {
        if (!$this->isAvailable) {
            $this->logger?->debug('Cannot delete document: Elasticsearch unavailable', [
                'index' => $this->getIndexName($index),
                'id' => $id
            ]);
            return;
        }
        
        if ($this->indexExists($index)) {
            try {
                $this->client->delete([
                    'index' => $this->getIndexName($index),
                    'id' => $id
                ]);
            } catch (\Exception $e) {
                $this->logger?->error('Failed to delete document from Elasticsearch', [
                    'index' => $this->getIndexName($index),
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    public function search(string $index, array $query): array
    {
        if (!$this->isAvailable) {
            $this->logger?->debug('Cannot search: Elasticsearch unavailable', [
                'index' => $this->getIndexName($index)
            ]);
            return ['hits' => ['hits' => [], 'total' => ['value' => 0]]];
        }
        
        try {
        $response = $this->client->search([
            'index' => $this->getIndexName($index),
            'body' => $query
        ]);
        
        return $response->asArray();
        } catch (\Exception $e) {
            $this->logger?->error('Elasticsearch search failed', [
                'index' => $this->getIndexName($index),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function bulk(array $operations): void
    {
        if (empty($operations)) {
            return;
        }

        if (!$this->isAvailable) {
            $this->logger?->debug('Cannot perform bulk operation: Elasticsearch unavailable');
            return;
        }

        try {
        $this->client->bulk(['body' => $operations]);
        } catch (\Exception $e) {
            $this->logger?->error('Elasticsearch bulk operation failed', [
                'error' => $e->getMessage(),
                'operations_count' => count($operations)
            ]);
            throw $e;
        }
    }
}

