<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\JobRequest;
use App\Entity\AppEvent;
use App\Entity\Complaint;
use App\Entity\Interview;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NotificationService
{
    private EntityManagerInterface $entityManager;
    private string $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag
    ) {
        $this->entityManager = $entityManager;
        $this->projectDir = $parameterBag->get('kernel.project_dir');
    }

    public function createNotification(string $type, string $message, ?User $user = null, ?string $route = null): void
    {
        $notification = [
            'type' => $type,
            'message' => $message,
            'user' => $user,
            'route' => $route,
            'createdAt' => new \DateTime(),
            'isRead' => false,
        ];

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function createJobApplicationNotification(JobRequest $application): void
    {
        $candidate = $application->getCandidate();
        $jobTitle = $application->getJobTitle();
        
        $message = sprintf(
            'New application received for %s from %s %s',
            $jobTitle,
            $candidate->getFirstName(),
            $candidate->getLastName()
        );

        $this->createNotification('job_application', $message, $candidate, 'app_applications');
    }

    public function createInterviewScheduledNotification(Interview $interview): void
    {
        $candidate = $interview->getJobRequest()->getCandidate();
        $jobTitle = $interview->getJobRequest()->getJobTitle();
        
        $message = sprintf(
            'Interview scheduled for %s on %s at %s',
            $jobTitle,
            $interview->getDateTime()->format('M j, Y'),
            $interview->getDateTime()->format('g:i A')
        );

        $this->createNotification('interview_scheduled', $message, $candidate, 'app_applications');
    }

    public function createEventRegistrationNotification(AppEvent $event, User $user): void
    {
        $message = sprintf(
            'Successfully registered for event: %s on %s',
            $event->getName(),
            $event->getEventDate()->format('M j, Y')
        );

        $this->createNotification('event_registration', $message, $user, 'app_events_show', ['id' => $event->getId()]);
    }

    public function createComplaintResponseNotification(Complaint $complaint): void
    {
        $user = $complaint->getUser();
        
        $message = sprintf(
            'Your complaint "%s" has received a response',
            $complaint->getSubject()
        );

        $this->createNotification('complaint_response', $message, $user, 'app_complaints_show', ['id' => $complaint->getId()]);
    }

    public function createJobPostedNotification(string $jobTitle): void
    {
        $message = sprintf('Your job posting "%s" has been published successfully', $jobTitle);
        
        // This would typically go to the HR user who posted the job
        // For now, we'll create a general notification
        $this->createNotification('job_posted', $message);
    }

    public function createSystemNotification(string $message, string $type = 'system'): void
    {
        $this->createNotification($type, $message);
    }

    public function getUnreadNotifications(?User $user = null): array
    {
        $qb = $this->entityManager->createQueryBuilder('n')
            ->where('n.isRead = :isRead')
            ->setParameter('isRead', false);

        if ($user) {
            $qb->andWhere('n.user = :user')
               ->setParameter('user', $user->getId());
        }

        return $qb->orderBy('n.createdAt', 'DESC')
                  ->setMaxResults(20)
                  ->getQuery()
                  ->getResult();
    }

    public function markAsRead(int $notificationId): void
    {
        $notification = $this->entityManager->find('App\Entity\Notification', $notificationId);
        
        if ($notification) {
            $notification->setIsRead(true);
            $notification->setReadAt(new \DateTime());
            $this->entityManager->flush();
        }
    }

    public function markAllAsRead(?User $user = null): void
    {
        $qb = $this->entityManager->createQueryBuilder('n')
            ->update('App\Entity\Notification', 'n')
            ->set('n.isRead', true)
            ->set('n.readAt', ':readAt');

        if ($user) {
            $qb->where('n.user = :user')
               ->andWhere('n.isRead = false')
               ->setParameter('user', $user->getId())
               ->setParameter('readAt', new \DateTime());
        } else {
            $qb->where('n.isRead = false')
               ->setParameter('readAt', new \DateTime());
        }

        $qb->getQuery()->execute();
    }

    public function getNotificationCount(?User $user = null): int
    {
        $qb = $this->entityManager->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.isRead = :isRead')
            ->setParameter('isRead', false);

        if ($user) {
            $qb->andWhere('n.user = :user')
               ->setParameter('user', $user->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function deleteNotification(int $notificationId): void
    {
        $notification = $this->entityManager->find('App\Entity\Notification', $notificationId);
        
        if ($notification) {
            $this->entityManager->remove($notification);
            $this->entityManager->flush();
        }
    }

    public function createRealtimeNotification(string $type, array $data): void
    {
        // This would typically integrate with WebSocket or Server-Sent Events
        // For now, we'll store in a temporary cache or database table
        
        $notification = [
            'type' => $type,
            'data' => json_encode($data),
            'createdAt' => new \DateTime(),
            'expiresAt' => new \DateTime('+5 minutes'), // Notifications expire after 5 minutes
        ];

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function getActiveNotifications(?User $user = null): array
    {
        $qb = $this->entityManager->createQueryBuilder('n')
            ->where('n.createdAt >= :threshold')
            ->setParameter('threshold', new \DateTime('-24 hours')); // Last 24 hours

        if ($user) {
            $qb->andWhere('n.user = :user')
               ->setParameter('user', $user->getId());
        }

        return $qb->orderBy('n.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
