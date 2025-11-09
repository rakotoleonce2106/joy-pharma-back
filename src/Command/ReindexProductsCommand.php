<?php

namespace App\Command;

use App\Service\ProductElasticsearchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reindex-products',
    description: 'Reindex all products in Elasticsearch'
)]
class ReindexProductsCommand extends Command
{
    public function __construct(
        private readonly ProductElasticsearchService $productElasticsearchService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Reindexing Products in Elasticsearch');

        try {
            $io->info('Initializing Elasticsearch index...');
            $this->productElasticsearchService->initializeIndex();

            $io->info('Reindexing all products...');
            $this->productElasticsearchService->reindexAll();

            $io->success('All products have been successfully reindexed in Elasticsearch!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to reindex products: ' . $e->getMessage());
            $io->error($e->getTraceAsString());
            
            return Command::FAILURE;
        }
    }
}

