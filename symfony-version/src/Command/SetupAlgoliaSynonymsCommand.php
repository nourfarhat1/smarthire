<?php

namespace App\Command;

use App\Service\AlgoliaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:setup-algolia-synonyms',
    description: 'Setup Algolia synonyms for enhanced typo tolerance'
)]
class SetupAlgoliaSynonymsCommand extends Command
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
            ->setDescription('Setup Algolia synonyms for enhanced typo tolerance')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force re-setup of synonyms (overwrite existing)'
            )
            ->setHelp('
This command sets up comprehensive synonyms in Algolia to enhance typo tolerance for job searches.

Examples:
  php bin/console app:setup-algolia-synonyms
  php bin/console app:setup-algolia-synonyms --force

What it does:
- Sets up job title synonyms (developer, designer, manager, etc.)
- Adds technology synonyms (javascript, python, etc.)
- Configures location synonyms (remote, wfh, etc.)
- Adds experience level synonyms (senior, junior, etc.)

Benefits:
- Enhanced typo tolerance for misspellings
- Better matching for abbreviations and variations
- Improved search results for users
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        $io->title('Algolia Synonyms Setup');

        // Check if Algolia is configured
        $appId = $_ENV['ALGOLIA_APP_ID'] ?? null;
        $adminKey = $_ENV['ALGOLIA_ADMIN_KEY'] ?? null;

        if (!$appId || $appId === 'YOUR_APP_ID' || !$adminKey || $adminKey === 'YOUR_ADMIN_API_KEY') {
            $io->error('Algolia is not configured properly!');
            $io->text([
                'Please configure your Algolia credentials in the .env file:',
                'ALGOLIA_APP_ID=your_app_id',
                'ALGOLIA_ADMIN_KEY=your_admin_api_key',
                'ALGOLIA_SEARCH_KEY=your_search_only_key',
                '',
                'Get your credentials from: https://www.algolia.com/'
            ]);
            return Command::FAILURE;
        }

        $io->text([
            'Setting up synonyms for enhanced typo tolerance...',
            '',
            'This will configure the following synonym groups:',
            '1. Job Titles: developer, designer, manager, analyst',
            '2. Experience Levels: senior, junior, lead, principal',
            '3. Technologies: javascript, python, etc.',
            '4. Work Locations: remote, office, wfh, etc.',
            ''
        ]);

        if (!$force) {
            if (!$io->confirm('Do you want to continue?', true)) {
                $io->text('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $io->section('Setting up synonyms...');

        try {
            // Create a new AlgoliaService instance to trigger synonym setup
            $this->algoliaService->setupSynonyms();

            $io->success('Synonyms setup completed successfully!');

            $io->section('Synonym Groups Created:');
            
            $synonymGroups = [
                'Job Titles' => ['developer', 'designer', 'manager', 'analyst'],
                'Experience Levels' => ['senior', 'junior', 'lead', 'principal'],
                'Technologies' => ['javascript', 'python'],
                'Work Locations' => ['remote', 'office', 'wfh']
            ];

            foreach ($synonymGroups as $group => $examples) {
                $io->text("  - {$group}: " . implode(', ', $examples));
            }

            $io->newLine();
            $io->text([
                'Enhanced search features now available:',
                '  - Typo tolerance: "developr" finds "developer"',
                '  - Abbreviations: "sr dev" finds "senior developer"',
                '  - Variations: "wfh" finds "work from home"',
                '  - Technology terms: "js" finds "javascript"',
                '',
                'Test the enhanced search at: /candidate/applications/search'
            ]);

        } catch (\Exception $e) {
            $io->error('Failed to setup synonyms: ' . $e->getMessage());
            $io->text([
                'Troubleshooting:',
                '1. Check your Algolia credentials in .env',
                '2. Ensure your Algolia account has API access',
                '3. Verify the index name "job_offers" exists',
                '4. Check your internet connection'
            ]);
            return Command::FAILURE;
        }

        $io->success('Algolia synonyms setup completed!');
        return Command::SUCCESS;
    }
}
