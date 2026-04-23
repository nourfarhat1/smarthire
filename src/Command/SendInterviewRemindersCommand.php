<?php

namespace App\Command;

use App\Service\InterviewService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:interviews:send-reminders',
    description: 'Send reminder emails for upcoming interviews'
)]
class SendInterviewRemindersCommand extends Command
{
    public function __construct(
        private InterviewService $interviewService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Sending Interview Reminders');

        try {
            $pendingReminders = $this->interviewService->getPendingReminders();
            
            if (empty($pendingReminders)) {
                $io->success('No pending reminders to send.');
                return Command::SUCCESS;
            }

            $io->progressStart(count($pendingReminders));
            $sentCount = 0;
            $failedCount = 0;

            foreach ($pendingReminders as $interview) {
                try {
                    if ($this->interviewService->sendReminder($interview)) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $io->error('Failed to send reminder for interview ID ' . $interview->getId() . ': ' . $e->getMessage());
                }
                $io->progressAdvance();
            }

            $io->progressFinish();

            $io->success("Reminders sent successfully!");
            $io->table(
                ['Status', 'Count'],
                [
                    ['Sent', $sentCount],
                    ['Failed', $failedCount],
                    ['Total', count($pendingReminders)],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to send reminders: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
