<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\JobOfferRepository;
use App\Repository\JobRequestRepository;
use App\Repository\QuizRepository;
use App\Repository\QuizResultRepository;
use App\Repository\ComplaintRepository;
use App\Repository\AppEventRepository;
use App\Repository\TrainingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private JobOfferRepository $jobOfferRepository,
        private JobRequestRepository $jobRequestRepository,
        private QuizRepository $quizRepository,
        private QuizResultRepository $quizResultRepository,
        private ComplaintRepository $complaintRepository,
        private AppEventRepository $eventRepository,
        private TrainingRepository $trainingRepository
    ) {
    }

    #[Route('/', name: 'app_admin')]
    public function index(): Response
    {
        return $this->dashboard();
    }

    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        // Get statistics
        $totalUsers = count($this->userRepository->findAll());
        $totalJobs = count($this->jobOfferRepository->findAll());
        $totalApplications = count($this->jobRequestRepository->findAll());
        $totalQuizzes = count($this->quizRepository->findAll());
        $totalQuizResults = count($this->quizResultRepository->findAll());
        $totalComplaints = count($this->complaintRepository->findAll());
        $totalEvents = count($this->eventRepository->findAll());
        $totalTrainings = count($this->trainingRepository->findAll());

        // Get recent activity
        $recentApplications = $this->jobRequestRepository->findBy([], ['submissionDate' => 'DESC'], 5);
        $recentComplaints = $this->complaintRepository->findBy([], ['submissionDate' => 'DESC'], 5);
        $recentUsers = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // User role statistics
        $candidates = count($this->userRepository->findBy(['roleId' => 1]));
        $hrUsers = count($this->userRepository->findBy(['roleId' => 2]));
        $adminUsers = count($this->userRepository->findBy(['roleId' => 3]));

        // Application status statistics
        $pendingApplications = count($this->jobRequestRepository->findBy(['status' => 'PENDING']));
        $approvedApplications = count($this->jobRequestRepository->findBy(['status' => 'APPROVED']));
        $rejectedApplications = count($this->jobRequestRepository->findBy(['status' => 'REJECTED']));

        // Quiz statistics
        $passedQuizzes = count($this->quizResultRepository->findBy(['isPassed' => true]));
        $failedQuizzes = count($this->quizResultRepository->findBy(['isPassed' => false]));

        // Complaint statistics
        $openComplaints = count($this->complaintRepository->findBy(['status' => 'OPEN']));
        $resolvedComplaints = count($this->complaintRepository->findBy(['status' => 'RESOLVED']));

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'total_users' => $totalUsers,
                'total_jobs' => $totalJobs,
                'total_applications' => $totalApplications,
                'total_quizzes' => $totalQuizzes,
                'total_quiz_results' => $totalQuizResults,
                'total_complaints' => $totalComplaints,
                'total_events' => $totalEvents,
                'total_trainings' => $totalTrainings,
            ],
            'user_stats' => [
                'candidates' => $candidates,
                'hr_users' => $hrUsers,
                'admin_users' => $adminUsers,
            ],
            'application_stats' => [
                'pending' => $pendingApplications,
                'approved' => $approvedApplications,
                'rejected' => $rejectedApplications,
            ],
            'quiz_stats' => [
                'passed' => $passedQuizzes,
                'failed' => $failedQuizzes,
            ],
            'complaint_stats' => [
                'open' => $openComplaints,
                'resolved' => $resolvedComplaints,
            ],
            'recent_applications' => $recentApplications,
            'recent_complaints' => $recentComplaints,
            'recent_users' => $recentUsers,
        ]);
    }
}
