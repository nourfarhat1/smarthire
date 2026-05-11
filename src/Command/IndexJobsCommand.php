<?php

namespace App\Command;

use App\Service\AlgoliaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:index-jobs',
    description: 'Index all job offers to Algolia'
)]
class IndexJobsCommand extends Command
{
    private AlgoliaService $algoliaService;

    public function __construct(AlgoliaService $algoliaService)
    {
        parent::__construct();
        $this->algoliaService = $algoliaService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Index all job offers to Algolia for search functionality')
            ->setHelp('This command indexes all existing job offers to Algolia so they can be searched using the autocomplete dropdown.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Indexing Jobs to Algolia');

        try {
            $indexedCount = $this->algoliaService->indexAllJobs();
            
            $io->success("Successfully indexed {$indexedCount} jobs to Algolia!");
            
            if ($indexedCount === 0) {
                $io->warning('No jobs were found to index. Make sure you have job offers in your database.');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to index jobs to Algolia: ' . $e->getMessage());
            $io->text([
                'Please check your Algolia configuration:',
                '1. ALGOLIA_APP_ID is correct',
                '2. ALGOLIA_ADMIN_KEY has proper permissions',
                '3. Your Algolia account is active',
                '',
                'You can get your credentials from: https://www.algolia.com/'
            ]);
            
            return Command::FAILURE;
        }
    }
}
