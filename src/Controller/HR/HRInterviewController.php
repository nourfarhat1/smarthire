<?php

namespace App\Controller\HR;

use App\Entity\Interview;
use App\Entity\JobRequest;
use App\Repository\InterviewRepository;
use App\Repository\JobRequestRepository;
use App\Service\InterviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hr/interviews')]
#[IsGranted('ROLE_HR')]
class HRInterviewController extends AbstractController
{
    public function __construct(
        private InterviewRepository $interviewRepository,
        private JobRequestRepository $jobRequestRepository,
        private InterviewService $interviewService
    ) {
    }

    #[Route('/', name: 'app_hr_interviews')]
    public function index(Request $request): Response
    {
        $hrUser = $this->getUser();
        
        // Get interviews for HR's own job applications
        $qb = $this->interviewRepository->createQueryBuilder('i')
            ->leftJoin('i.jobRequest', 'jr')
            ->leftJoin('jr.jobOffer', 'jo')
            ->leftJoin('jr.candidate', 'c')
            ->where('jo.recruiter = :hrUser')
            ->setParameter('hrUser', $hrUser)
            ->orderBy('i.dateTime', 'DESC');

        // Apply status filter
        $status = $request->query->get('status', '');
        if (!empty($status)) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $status);
        }

        $interviews = $qb->getQuery()->getResult();

        // Calculate statistics
        $totalCount = count($interviews);
        $scheduledCount = count(array_filter($interviews, fn($i) => $i->getStatus() === 'SCHEDULED'));
        $completedCount = count(array_filter($interviews, fn($i) => $i->getStatus() === 'COMPLETED'));
        $cancelledCount = count(array_filter($interviews, fn($i) => $i->getStatus() === 'CANCELLED'));

        return $this->render('hr/interviews/index.html.twig', [
            'interviews' => $interviews,
            'totalCount' => $totalCount,
            'scheduledCount' => $scheduledCount,
            'completedCount' => $completedCount,
            'cancelledCount' => $cancelledCount,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/schedule/{jobRequestId}', name: 'app_hr_interviews_schedule', methods: ['GET', 'POST'])]
    public function schedule(Request $request, int $jobRequestId): Response
    {
        $hrUser = $this->getUser();
        
        // Find the job request and verify it belongs to HR's job offer
        $jobRequest = $this->jobRequestRepository->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'jo')
            ->where('jr.id = :jobRequestId')
            ->andWhere('jo.recruiter = :hrUser')
            ->setParameter('jobRequestId', $jobRequestId)
            ->setParameter('hrUser', $hrUser)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$jobRequest) {
            $this->addFlash('error', 'Job request not found or access denied.');
            return $this->redirectToRoute('app_hr_interviews');
        }

        // Check if interview already exists
        $existingInterview = $this->interviewRepository->findOneBy(['jobRequest' => $jobRequest]);
        if ($existingInterview) {
            $this->addFlash('warning', 'An interview is already scheduled for this application.');
            return $this->redirectToRoute('app_hr_interviews');
        }

        if ($request->isMethod('POST')) {
            $dateTime = new \DateTime($request->request->get('dateTime'));
            $location = $request->request->get('location');
            $isOnline = $request->request->get('isOnline') === 'on';
            $notes = $request->request->get('notes');

            try {
                $interview = $this->interviewService->scheduleInterview(
                    $jobRequest,
                    $dateTime,
                    $location ?: null,
                    $isOnline,
                    $notes ?: null
                );

                $this->addFlash('success', 'Interview scheduled successfully! Email notification sent to candidate.');
                return $this->redirectToRoute('app_hr_interviews');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to schedule interview: ' . $e->getMessage());
            }
        }

        return $this->render('hr/interviews/schedule.html.twig', [
            'jobRequest' => $jobRequest,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_hr_interviews_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Interview $interview): Response
    {
        // Check if the interview belongs to HR's job offer
        $hrUser = $this->getUser();
        $jobRequest = $interview->getJobRequest();
        
        if ($jobRequest->getJobOffer()->getRecruiter() !== $hrUser) {
            throw $this->createAccessDeniedException('You can only edit interviews for your own job offers.');
        }

        if ($request->isMethod('POST')) {
            $dateTime = $request->request->get('dateTime');
            $location = $request->request->get('location');
            $notes = $request->request->get('notes');
            $status = $request->request->get('status');
            
            // Basic validation
            if (empty($dateTime) || empty($location) || empty($status)) {
                $this->addFlash('error', 'Please fill in all required fields.');
                return $this->redirectToRoute('app_hr_interviews_edit', ['id' => $interview->getId()]);
            }
            
            // Parse and validate date
            try {
                $interviewDateTime = new \DateTime($dateTime);
                if ($interviewDateTime < new \DateTime()) {
                    $this->addFlash('error', 'Interview date cannot be in the past.');
                    return $this->redirectToRoute('app_hr_interviews_edit', ['id' => $interview->getId()]);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid date format.');
                return $this->redirectToRoute('app_hr_interviews_edit', ['id' => $interview->getId()]);
            }
            
            // Update interview data
            $interview->setDateTime($interviewDateTime);
            $interview->setLocation($location);
            $interview->setNotes($notes);
            $interview->setStatus($status);

            // Update job request status based on interview status
            if ($status === 'COMPLETED') {
                $jobRequest->setStatus('INTERVIEW_COMPLETED');
            } elseif ($status === 'CANCELLED') {
                $jobRequest->setStatus('INTERVIEW_CANCELLED');
            } else {
                $jobRequest->setStatus('INTERVIEW_SCHEDULED');
            }
            $this->jobRequestRepository->save($jobRequest, true);

            // Save to database
            $this->interviewRepository->save($interview, true);
            $this->addFlash('success', 'Interview has been updated successfully!');

            return $this->redirectToRoute('app_hr_interviews');
        }

        return $this->render('hr/interviews/edit.html.twig', [
            'interview' => $interview,
            'jobRequest' => $jobRequest,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_hr_interviews_delete', methods: ['POST'])]
    public function delete(Request $request, Interview $interview): Response
    {
        // Check if the interview belongs to HR's job offer
        $hrUser = $this->getUser();
        $jobRequest = $interview->getJobRequest();
        
        if ($jobRequest->getJobOffer()->getRecruiter() !== $hrUser) {
            throw $this->createAccessDeniedException('You can only delete interviews for your own job offers.');
        }

        // Update job request status
        $jobRequest->setStatus('PENDING');
        $this->jobRequestRepository->save($jobRequest, true);

        $this->interviewRepository->remove($interview, true);
        $this->addFlash('success', 'Interview has been deleted successfully!');

        return $this->redirectToRoute('app_hr_interviews');
    }

    #[Route('/calendar', name: 'app_hr_interviews_calendar')]
    public function calendar(): Response
    {
        $hrUser = $this->getUser();
        
        // Get interviews for HR's own job applications
        $interviews = $this->interviewRepository->createQueryBuilder('i')
            ->leftJoin('i.jobRequest', 'jr')
            ->leftJoin('jr.jobOffer', 'jo')
            ->leftJoin('jr.candidate', 'c')
            ->where('jo.recruiter = :hrUser')
            ->andWhere('i.status = :status')
            ->setParameter('hrUser', $hrUser)
            ->setParameter('status', 'SCHEDULED')
            ->orderBy('i.dateTime', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('hr/interviews/calendar.html.twig', [
            'interviews' => $interviews,
        ]);
    }

    #[Route('/reschedule/{id}', name: 'app_hr_interviews_reschedule', methods: ['GET', 'POST'])]
    public function reschedule(Request $request, Interview $interview): Response
    {
        if ($request->isMethod('POST')) {
            $newDateTime = new \DateTime($request->request->get('dateTime'));
            
            if ($this->interviewService->rescheduleInterview($interview, $newDateTime)) {
                $this->addFlash('success', 'Interview rescheduled successfully! Email notification sent to candidate.');
            } else {
                $this->addFlash('error', 'Failed to reschedule interview.');
            }
            
            return $this->redirectToRoute('app_hr_interviews');
        }

        return $this->render('hr/interviews/reschedule.html.twig', [
            'interview' => $interview,
        ]);
    }

    #[Route('/cancel/{id}', name: 'app_hr_interviews_cancel', methods: ['POST'])]
    public function cancel(Request $request, Interview $interview): Response
    {
        $reason = $request->request->get('reason', '');
        
        if ($this->interviewService->cancelInterview($interview, $reason)) {
            $this->addFlash('success', 'Interview cancelled successfully! Email notification sent to candidate.');
        } else {
            $this->addFlash('error', 'Failed to cancel interview.');
        }
        
        return $this->redirectToRoute('app_hr_interviews');
    }

    #[Route('/send-reminder/{id}', name: 'app_hr_interviews_send_reminder', methods: ['POST'])]
    public function sendReminder(Interview $interview): Response
    {
        if ($this->interviewService->sendReminder($interview)) {
            $this->addFlash('success', 'Reminder sent successfully to candidate.');
        } else {
            $this->addFlash('error', 'Failed to send reminder.');
        }
        
        return $this->redirectToRoute('app_hr_interviews');
    }

    #[Route('/batch-reminders', name: 'app_hr_interviews_batch_reminders', methods: ['POST'])]
    public function sendBatchReminders(): Response
    {
        $pendingReminders = $this->interviewService->getPendingReminders();
        $sentCount = 0;
        
        foreach ($pendingReminders as $interview) {
            if ($this->interviewService->sendReminder($interview)) {
                $sentCount++;
            }
        }
        
        $this->addFlash('success', "Sent reminders to {$sentCount} candidates.");
        return $this->redirectToRoute('app_hr_interviews');
    }
}
