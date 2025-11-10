<?php

namespace App\Service;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;

class ElasticsearchService
{
    private Client $client;
    private string $indexPrefix;

    public function __construct(
        array $hosts,
        string $indexPrefix,
        ?LoggerInterface $logger = null
    ) {
        // Handle defaults - read from environment variables using getenv() which works better in Symfony
        $elasticsearchHost = getenv('ELASTICSEARCH_HOST') 
            ?: ($_ENV['ELASTICSEARCH_HOST'] ?? null)
            ?: ($_SERVER['ELASTICSEARCH_HOST'] ?? null);
        
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
        
        $elasticsearchPrefix = getenv('ELASTICSEARCH_INDEX_PREFIX')
            ?: ($_ENV['ELASTICSEARCH_INDEX_PREFIX'] ?? null)
            ?: ($_SERVER['ELASTICSEARCH_INDEX_PREFIX'] ?? null);
        $this->indexPrefix = $indexPrefix ?: ($elasticsearchPrefix ?: 'joy_pharma');
        
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->setLogger($logger)
            ->build();
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

    public function indexExists(string $index): bool
    {
        try {
            $response = $this->client->indices()->exists(['index' => $this->getIndexName($index)]);
            return $response->asBool();
        } catch (\Exception $e) {
            // If there's an exception (e.g., index doesn't exist), return false
            return false;
        }
    }

    public function createIndex(string $index, array $mapping): void
    {
        $indexName = $this->getIndexName($index);
        
        if ($this->indexExists($index)) {
            return;
        }

        $this->client->indices()->create([
            'index' => $indexName,
            'body' => [
                'mappings' => $mapping
            ]
        ]);
    }

    public function indexDocument(string $index, string $id, array $document): void
    {
        $this->client->index([
            'index' => $this->getIndexName($index),
            'id' => $id,
            'body' => $document
        ]);
    }

    public function updateDocument(string $index, string $id, array $document): void
    {
        $this->client->update([
            'index' => $this->getIndexName($index),
            'id' => $id,
            'body' => [
                'doc' => $document
            ]
        ]);
    }

    public function deleteDocument(string $index, string $id): void
    {
        if ($this->indexExists($index)) {
            $this->client->delete([
                'index' => $this->getIndexName($index),
                'id' => $id
            ]);
        }
    }

    public function search(string $index, array $query): array
    {
        return $this->client->search([
            'index' => $this->getIndexName($index),
            'body' => $query
        ]);
    }

    public function bulk(array $operations): void
    {
        if (empty($operations)) {
            return;
        }

        $this->client->bulk(['body' => $operations]);
    }
}

