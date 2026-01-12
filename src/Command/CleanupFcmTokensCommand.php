<?php

namespace App\Command;

use App\Service\FcmTokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to clean up stale and inactive FCM device tokens.
 * 
 * This should be run periodically (e.g., daily via cron) to:
 * - Remove tokens that have too many failed push attempts
 * - Remove tokens that haven't been used in a long time
 * 
 * Usage:
 *   php bin/console app:cleanup-fcm-tokens
 *   php bin/console app:cleanup-fcm-tokens --dry-run
 */
#[AsCommand(
    name: 'app:cleanup-fcm-tokens',
    description: 'Clean up stale and inactive FCM device tokens'
)]
class CleanupFcmTokensCommand extends Command
{
    public function __construct(
        private readonly FcmTokenService $fcmTokenService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Only show what would be deleted without actually deleting'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('FCM Token Cleanup');

        $isDryRun = $input->getOption('dry-run');

        if ($isDryRun) {
            $io->note('Running in dry-run mode - no tokens will be deleted');
        }

        if (!$isDryRun) {
            $result = $this->fcmTokenService->cleanupTokens();

            $io->success(sprintf(
                'Cleanup completed! Removed %d tokens (%d stale, %d inactive)',
                $result['total_removed'],
                $result['stale_removed'],
                $result['inactive_removed']
            ));
        } else {
            $io->info('Dry run completed. Run without --dry-run to actually delete tokens.');
        }

        return Command::SUCCESS;
    }
}
