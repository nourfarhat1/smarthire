<?php

namespace App\Command;

use App\Service\AlgoliaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:index-jobs', description: 'Index all jobs to Algolia')]
class IndexJobsCommand extends Command
{
    public function __construct(private AlgoliaService $algoliaService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Indexing jobs...');
        $count = $this->algoliaService->indexAllJobs();
        $output->writeln("Done! $count jobs indexed.");
        return Command::SUCCESS;
    }
}