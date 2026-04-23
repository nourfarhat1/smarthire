<?php

namespace App\EventSubscriber;

use App\Repository\JobRequestRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JobRequestRepository $jobRequestRepository
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(SetDataEvent $setDataEvent)
    {
        $start = $setDataEvent->getStart();
        $end = $setDataEvent->getEnd();
        $filters = $setDataEvent->getFilters();

        // Get current user to show only their applications
        $user = $filters['user'] ?? null;

        // Fetch job requests between the calendar's current start and end dates
        $queryBuilder = $this->jobRequestRepository->createQueryBuilder('jr')
            ->where('jr.submissionDate BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        // Filter by current user if specified
        if ($user) {
            $queryBuilder->andWhere('jr.candidate = :user')
                ->setParameter('user', $user);
        }

        $jobRequests = $queryBuilder->getQuery()->getResult();

        // Group applications by date
        $applicationsByDate = [];
        foreach ($jobRequests as $jobRequest) {
            $dateKey = $jobRequest->getSubmissionDate()->format('Y-m-d');
            if (!isset($applicationsByDate[$dateKey])) {
                $applicationsByDate[$dateKey] = [];
            }
            
            $applicationsByDate[$dateKey][] = [
                'title' => $jobRequest->getJobTitle() ?: 'Job Application',
                'status' => $jobRequest->getStatus(),
                'appliedDate' => $jobRequest->getSubmissionDate()->format('Y-m-d H:i')
            ];
        }

        // Create events for each date
        foreach ($applicationsByDate as $date => $applications) {
            $dateObj = new \DateTime($date);
            
            // Determine color based on combined status
            $dateColor = $this->getDateColor($applications);
            
            // Create event with count
            $event = new Event(
                '📋 ' . count($applications) . ' Application' . (count($applications) > 1 ? 's' : ''),
                $dateObj
            );

            $event->setOptions([
                'backgroundColor' => $dateColor,
                'borderColor' => $dateColor,
                'textColor' => 'white',
                'display' => 'background-color',
                'extendedProps' => [
                    'applications' => $applications,
                    'date' => $date,
                    'isDateEvent' => true
                ]
            ]);

            $setDataEvent->addEvent($event);
        }
    }

    private function getDateColor(array $applications): string
    {
        $hasAccepted = false;
        $hasRejected = false;
        $hasInterviewing = false;
        $hasPending = false;

        foreach ($applications as $app) {
            switch ($app['status']) {
                case 'ACCEPTED':
                    $hasAccepted = true;
                    break;
                case 'REJECTED':
                    $hasRejected = true;
                    break;
                case 'INTERVIEWING':
                    $hasInterviewing = true;
                    break;
                case 'PENDING':
                    $hasPending = true;
                    break;
            }
        }

        // Priority color coding
        if ($hasAccepted && !$hasRejected && !$hasInterviewing && !$hasPending) {
            return '#28a745'; // Green - only accepted
        } elseif ($hasRejected && !$hasAccepted && !$hasInterviewing && !$hasPending) {
            return '#dc3545'; // Red - only rejected
        } elseif ($hasInterviewing && !$hasAccepted && !$hasRejected && !$hasPending) {
            return '#ff69b4'; // Pink - only interviewing
        } elseif ($hasPending && !$hasAccepted && !$hasRejected && !$hasInterviewing) {
            return '#ffc107'; // Yellow - only pending
        } elseif ($hasPending && $hasRejected && !$hasAccepted && !$hasInterviewing) {
            return '#ff8c00'; // Orange - pending + rejected
        } else {
            return '#6c757d'; // Gray - mixed statuses
        }
    }
}
