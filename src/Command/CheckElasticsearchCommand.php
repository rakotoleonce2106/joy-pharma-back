<?php

namespace App\Command;

use App\Service\ElasticsearchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-elasticsearch',
    description: 'Vérifie l\'état de connexion à Elasticsearch'
)]
class CheckElasticsearchCommand extends Command
{
    public function __construct(
        private readonly ElasticsearchService $elasticsearchService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Vérification de l\'état d\'Elasticsearch');
        
        // Check availability
        $isAvailable = $this->elasticsearchService->isAvailable();
        
        if (!$isAvailable) {
            $io->warning('Elasticsearch n\'est pas disponible actuellement.');
            $io->note('Vérification de la connexion...');
            
            // Try to check availability
            $checkResult = $this->elasticsearchService->checkAvailability();
            
            if (!$checkResult) {
                $io->error('Impossible de se connecter à Elasticsearch.');
                $io->section('Solutions possibles :');
                $io->listing([
                    'Vérifiez que le service Elasticsearch est démarré',
                    'Vérifiez la variable d\'environnement ELASTICSEARCH_HOST',
                    'Consultez la documentation : docs/ELASTICSEARCH_TROUBLESHOOTING.md',
                    'Pour désactiver Elasticsearch temporairement : ELASTICSEARCH_ENABLED=false'
                ]);
                return Command::FAILURE;
            }
        }
        
        $io->success('Elasticsearch est disponible et accessible !');
        
        // Check if index exists
        $indexName = $this->elasticsearchService->getIndexName('products');
        $indexExists = $this->elasticsearchService->indexExists('products');
        
        $io->section('Informations sur l\'index');
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['Nom de l\'index', $indexName],
                ['Index existe', $indexExists ? 'Oui' : 'Non'],
            ]
        );
        
        if (!$indexExists) {
            $io->note('L\'index n\'existe pas encore. Exécutez la commande suivante pour créer l\'index et réindexer les produits :');
            $io->text('  bin/console app:reindex-products');
        }
        
        return Command::SUCCESS;
    }
}

