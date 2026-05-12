<?php

namespace App\Controller\Application;

use App\Entity\JobRequest;
use App\Repository\JobRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/applications')]
#[IsGranted('ROLE_USER')]
class MyApplicationsController extends AbstractController
{
    public function __construct(
        private JobRequestRepository $jobRequestRepository
    ) {
    }

    #[Route('/', name: 'app_applications_general')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $status = $request->query->get('status', '');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        $applications = $this->jobRequestRepository->findByCandidateWithFilters(
            $user->getId(),
            $status,
            $dateFrom ? new \DateTime($dateFrom) : null,
            $dateTo ? new \DateTime($dateTo) : null
        );

        // Calculate statistics
        $totalApplications = count($applications);
        $pendingCount = count(array_filter($applications, fn($app) => $app->getStatus() === 'PENDING'));
        $approvedCount = count(array_filter($applications, fn($app) => $app->getStatus() === 'APPROVED'));
        $interviewCount = count(array_filter($applications, fn($app) => $app->getInterviews()->count() > 0));

        return $this->render('applications/index.html.twig', [
            'applications' => $applications,
            'totalApplications' => $totalApplications,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'interviewCount' => $interviewCount,
        ]);
    }

    #[Route('/{id}', name: 'app_applications_show')]
    public function show(JobRequest $jobRequest): Response
    {
        // Check if the user owns this application
        if ($jobRequest->getCandidate() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only view your own applications.');
        }

        return $this->render('applications/show.html.twig', [
            'application' => $jobRequest,
        ]);
    }

    #[Route('/{id}/withdraw', name: 'app_applications_withdraw', methods: ['POST'])]
    public function withdraw(JobRequest $jobRequest): Response
    {
        // Check if the user owns this application
        if ($jobRequest->getCandidate() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only withdraw your own applications.');
        }

        // Only allow withdrawal if status is PENDING
        if ($jobRequest->getStatus() !== 'PENDING') {
            $this->addFlash('error', 'You can only withdraw pending applications.');
            return $this->redirectToRoute('app_applications_show', ['id' => $jobRequest->getId()]);
        }

        $jobRequest->setStatus('WITHDRAWN');
        $this->jobRequestRepository->save($jobRequest, true);
        $this->addFlash('success', 'Your application has been withdrawn successfully.');

        return $this->redirectToRoute('app_applications_general');
    }
}
