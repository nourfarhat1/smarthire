<?php

namespace App\Service;

use App\Entity\Interview;
use App\Entity\JobRequest;
use App\Repository\InterviewRepository;
use Doctrine\ORM\EntityManagerInterface;

class InterviewService
{
    public function __construct(
        private InterviewRepository $interviewRepository,
        private EntityManagerInterface $entityManager,
        private MailingService $mailingService,
        private GoogleCalendarService $googleCalendarService
    ) {
    }

    public function scheduleInterview(
        JobRequest $jobRequest,
        \DateTimeInterface $dateTime,
        ?string $location = null,
        bool $isOnline = true,
        ?string $notes = null
    ): Interview {
        $interview = new Interview();
        $interview->setJobRequest($jobRequest);
        $interview->setDateTime($dateTime);
        $interview->setLocation($location ?: ($isOnline ? 'Online Interview' : 'Office'));
        $interview->setNotes($notes);

        // Generate meeting link if online (stored in notes for now)
        if ($isOnline) {
            $meetingLink = $this->googleCalendarService->createInterviewEvent(
                $jobRequest->getCandidate()->getEmail(),
                $jobRequest->getJobOffer()->getTitle(),
                $dateTime,
                $isOnline
            );
            
            // Add meeting link to notes for now
            $currentNotes = $interview->getNotes() ?: '';
            $interview->setNotes($currentNotes . "\n\nGoogle Meet Link: " . $meetingLink);
        }

        // Save the interview
        $this->entityManager->persist($interview);
        $this->entityManager->flush();

        // Send email notification
        $candidate = $jobRequest->getCandidate();
        $meetingLink = $isOnline ? $this->extractMeetLinkFromNotes($interview->getNotes()) : null;
        $emailSent = $this->mailingService->sendInterviewNotification(
            $candidate->getEmail(),
            $candidate->getFullName(),
            $jobRequest->getJobOffer()->getTitle(),
            $dateTime->format('Y-m-d H:i'),
            $meetingLink
        );

        return $interview;
    }

    private function extractMeetLinkFromNotes(?string $notes): ?string
    {
        if (!$notes) return null;
        
        if (preg_match('/Google Meet Link: (https:\/\/meet\.google\.com\/[^\s]+)/', $notes, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    public function rescheduleInterview(Interview $interview, \DateTimeInterface $newDateTime): bool
    {
        try {
            $oldDateTime = $interview->getDateTime();
            $interview->setDateTime($newDateTime);
            $this->entityManager->flush();

            // Send reschedule notification
            $candidate = $interview->getJobRequest()->getCandidate();
            $meetingLink = $this->extractMeetLinkFromNotes($interview->getNotes());
            $this->mailingService->sendInterviewNotification(
                $candidate->getEmail(),
                $candidate->getFullName(),
                $interview->getJobRequest()->getJobOffer()->getTitle(),
                $newDateTime->format('Y-m-d H:i') . ' (Rescheduled from ' . $oldDateTime->format('Y-m-d H:i') . ')',
                $meetingLink
            );

            return true;
        } catch (\Exception $e) {
            error_log('Failed to reschedule interview: ' . $e->getMessage());
            return false;
        }
    }

    public function cancelInterview(Interview $interview, string $reason = ''): bool
    {
        try {
            $interview->setStatus('CANCELLED');
            $this->entityManager->flush();

            // Send cancellation notification
            $candidate = $interview->getJobRequest()->getCandidate();
            $this->mailingService->sendInterviewNotification(
                $candidate->getEmail(),
                $candidate->getFullName(),
                $interview->getJobRequest()->getJobOffer()->getTitle(),
                'CANCELLED - ' . $reason,
                $this->extractMeetLinkFromNotes($interview->getNotes())
            );

            return true;
        } catch (\Exception $e) {
            error_log('Failed to cancel interview: ' . $e->getMessage());
            return false;
        }
    }

    public function sendReminder(Interview $interview): bool
    {
        try {
            $candidate = $interview->getJobRequest()->getCandidate();
            $meetingLink = $this->extractMeetLinkFromNotes($interview->getNotes());
            $reminderSent = $this->mailingService->sendInterviewReminder(
                $candidate->getEmail(),
                $candidate->getFullName(),
                $interview->getJobRequest()->getJobOffer()->getTitle(),
                $interview->getDateTime()->format('Y-m-d H:i'),
                $meetingLink
            );

            return $reminderSent;
        } catch (\Exception $e) {
            error_log('Failed to send reminder: ' . $e->getMessage());
            return false;
        }
    }

    public function getUpcomingInterviews(): array
    {
        return $this->interviewRepository->findBy(
            ['status' => 'SCHEDULED'],
            ['dateTime' => 'ASC']
        );
    }

    public function getInterviewsForDate(\DateTimeInterface $date): array
    {
        return $this->interviewRepository->findInterviewsForDate($date);
    }

    public function getPendingReminders(): array
    {
        $now = new \DateTime();
        $tomorrow = (clone $now)->add(new \DateInterval('P1D'));
        
        return $this->interviewRepository->createQueryBuilder('i')
            ->where('i.dateTime BETWEEN :now AND :tomorrow')
            ->andWhere('i.status = :status')
            ->setParameter('now', $now)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('status', 'SCHEDULED')
            ->getQuery()
            ->getResult();
    }
}
