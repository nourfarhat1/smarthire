<?php

namespace App\Controller\Candidate;

use App\Repository\JobRequestRepository;
use App\Repository\JobOfferRepository;
use App\Repository\QuizResultRepository;
use App\Repository\EventParticipantRepository;
use App\Repository\TrainingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidate')]
class CandidateDashboardController extends AbstractController
{
    public function __construct(
        private JobRequestRepository $jobRequestRepository,
        private JobOfferRepository $jobOfferRepository,
        private QuizResultRepository $quizResultRepository,
        private EventParticipantRepository $eventParticipantRepository,
        private TrainingRepository $trainingRepository
    ) {
    }

    #[Route('/', name: 'app_candidate_dashboard')]
    #[IsGranted('ROLE_CANDIDATE')]
    public function dashboard(): Response
    {
        // Get candidate user object
        $candidate = $this->getUser();
        
        // Application statistics
        $totalApplications = count($this->jobRequestRepository->findBy(['candidate' => $candidate]));
        $pendingApplications = count($this->jobRequestRepository->findBy(['candidate' => $candidate, 'status' => 'Pending']));
        $acceptedApplications = count($this->jobRequestRepository->findBy(['candidate' => $candidate, 'status' => 'Accepted']));
        $rejectedApplications = count($this->jobRequestRepository->findBy(['candidate' => $candidate, 'status' => 'Rejected']));
        
        // Job search progress
        $recentApplications = $this->jobRequestRepository->findBy(['candidate' => $candidate], ['submissionDate' => 'DESC'], 5);
        $recommendedJobs = $this->jobOfferRepository->findRecommendedJobsForCandidate($candidate, 5);
        
        // Quiz and training progress
        $completedQuizzes = count($this->quizResultRepository->findBy(['candidate' => $candidate]));
        $upcomingEvents = $this->eventParticipantRepository->findUpcomingEventsForCandidate($candidate);
        $availableTrainings = $this->trainingRepository->findAvailableTrainingsForCandidate($candidate, 3);
        
        // Calculate application success rate
        $successRate = $totalApplications > 0 ? round(($acceptedApplications / $totalApplications) * 100, 1) : 0;
        
        // Job search activity
        $applicationsThisMonth = count($this->jobRequestRepository->findApplicationsThisMonth($candidate));
        $profileCompletion = $this->calculateProfileCompletion($candidate);

        return $this->render('candidate/dashboard.html.twig', [
            'stats' => [
                'total_applications' => $totalApplications,
                'pending_applications' => $pendingApplications,
                'accepted_applications' => $acceptedApplications,
                'rejected_applications' => $rejectedApplications,
                'success_rate' => $successRate,
                'completed_quizzes' => $completedQuizzes,
                'upcoming_events' => count($upcomingEvents),
                'applications_this_month' => $applicationsThisMonth,
                'profile_completion' => $profileCompletion,
            ],
            'recent_applications' => $recentApplications,
            'recommended_jobs' => $recommendedJobs,
            'upcoming_events' => $upcomingEvents,
            'available_trainings' => $availableTrainings,
            'saved_jobs' => $candidate->getSavedJobs(),
        ]);
    }
    
    private function calculateProfileCompletion($candidate): int
    {
        $fields = [
            'firstName' => !empty($candidate->getFirstName()),
            'lastName' => !empty($candidate->getLastName()),
            'phoneNumber' => !empty($candidate->getPhoneNumber()),
            'profilePicture' => !empty($candidate->getProfilePicture()),
        ];
        
        $completedFields = count(array_filter($fields));
        return round(($completedFields / count($fields)) * 100);
    }
}
