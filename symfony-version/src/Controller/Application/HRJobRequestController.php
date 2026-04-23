<?php

namespace App\Controller\Application;

use App\Entity\JobRequest;
use App\Repository\JobRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hr/applications')]
#[IsGranted('ROLE_HR')]
class HRJobRequestController extends AbstractController
{
    public function __construct(
        private JobRequestRepository $jobRequestRepository
    ) {
    }

    #[Route('/', name: 'app_hr_applications')]
    public function index(Request $request): Response
    {
        $hrId = $this->getUser()->getId();
        $status = $request->query->get('status', '');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        $applications = $this->jobRequestRepository->findByHRWithFilters(
            $hrId,
            $status,
            $dateFrom ? new \DateTime($dateFrom) : null,
            $dateTo ? new \DateTime($dateTo) : null
        );

        // Calculate statistics
        $totalApplications = count($applications);
        $pendingCount = count(array_filter($applications, fn($app) => $app->getStatus() === 'PENDING'));
        $approvedCount = count(array_filter($applications, fn($app) => $app->getStatus() === 'APPROVED'));
        $interviewCount = count(array_filter($applications, fn($app) => $app->getInterviews()->count() > 0));

        return $this->render('hr/applications/index.html.twig', [
            'applications' => $applications,
            'totalApplications' => $totalApplications,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'interviewCount' => $interviewCount,
        ]);
    }

    #[Route('/{id}', name: 'app_hr_applications_show')]
    public function show(JobRequest $jobRequest): Response
    {
        // Check if this application belongs to current HR user
        if ($jobRequest->getJobOffer()->getRecruiter()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only view applications for your job postings.');
        }

        return $this->render('hr/applications/show.html.twig', [
            'application' => $jobRequest,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_hr_applications_approve', methods: ['POST'])]
    public function approve(JobRequest $jobRequest): Response
    {
        // Check if this application belongs to current HR user
        if ($jobRequest->getJobOffer()->getRecruiter()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only approve applications for your job postings.');
        }

        $jobRequest->setStatus('APPROVED');
        $jobRequest->setReviewedAt(new \DateTime());
        $this->jobRequestRepository->save($jobRequest, true);

        $this->addFlash('success', 'Application approved successfully!');

        return $this->redirectToRoute('app_hr_applications_show', ['id' => $jobRequest->getId()]);
    }

    #[Route('/{id}/reject', name: 'app_hr_applications_reject', methods: ['POST'])]
    public function reject(Request $request, JobRequest $jobRequest): Response
    {
        // Check if this application belongs to current HR user
        if ($jobRequest->getJobOffer()->getRecruiter()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only reject applications for your job postings.');
        }

        $rejectionReason = $request->request->get('rejection_reason');
        
        $jobRequest->setStatus('REJECTED');
        $jobRequest->setRejectionReason($rejectionReason);
        $jobRequest->setReviewedAt(new \DateTime());
        $this->jobRequestRepository->save($jobRequest, true);

        $this->addFlash('success', 'Application rejected successfully!');

        return $this->redirectToRoute('app_hr_applications_show', ['id' => $jobRequest->getId()]);
    }

    #[Route('/{id}/schedule-interview', name: 'app_hr_applications_schedule_interview')]
    public function scheduleInterview(JobRequest $jobRequest): Response
    {
        // Check if this application belongs to current HR user
        if ($jobRequest->getJobOffer()->getRecruiter()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only schedule interviews for your job postings.');
        }

        return $this->render('hr/applications/schedule_interview.html.twig', [
            'application' => $jobRequest,
        ]);
    }

    #[Route('/dashboard', name: 'app_hr_applications_dashboard')]
    public function dashboard(): Response
    {
        $hrId = $this->getUser()->getId();
        
        // Get recent applications
        $recentApplications = $this->jobRequestRepository->findRecentByHR($hrId, 10);
        
        // Get statistics
        $stats = $this->jobRequestRepository->getHRStatistics($hrId);

        return $this->render('hr/applications/dashboard.html.twig', [
            'recentApplications' => $recentApplications,
            'stats' => $stats,
        ]);
    }

    #[Route('/spontaneous', name: 'app_hr_job_requests')]
    public function spontaneousApplications(): Response
    {
        $hrId = $this->getUser()->getId();
        $spontaneousApplications = $this->jobRequestRepository->findSpontaneousApplicationsForHR($hrId);

        return $this->render('hr/spontaneous_applications.html.twig', [
            'applications' => $spontaneousApplications,
        ]);
    }
}
