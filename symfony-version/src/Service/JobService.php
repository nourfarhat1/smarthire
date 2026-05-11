<?php

namespace App\Service;

use App\Entity\JobOffer;
use App\Entity\JobRequest;
use App\Entity\User;
use App\Repository\JobOfferRepository;
use App\Repository\JobRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class JobService
{
    private JobOfferRepository $jobOfferRepository;
    private JobRequestRepository $jobRequestRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        JobOfferRepository $jobOfferRepository,
        JobRequestRepository $jobRequestRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->jobOfferRepository = $jobOfferRepository;
        $this->jobRequestRepository = $jobRequestRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Check if a user has already applied for a specific job
     */
    public function hasUserAppliedForJob(User $user, JobOffer $jobOffer): bool
    {
        $existingApplication = $this->jobRequestRepository->findOneBy([
            'candidate' => $user,
            'jobOffer' => $jobOffer
        ]);

        return $existingApplication !== null;
    }

    /**
     * Apply for a job with duplicate checking
     */
    public function applyForJob(User $user, JobOffer $jobOffer, array $applicationData): array
    {
        // Check if user has already applied
        if ($this->hasUserAppliedForJob($user, $jobOffer)) {
            return [
                'success' => false,
                'error' => 'You have already applied for this job',
                'existing_application' => true
            ];
        }

        // Check if job is still active
        if (!$jobOffer->isActive()) {
            return [
                'success' => false,
                'error' => 'This job is no longer accepting applications',
                'job_inactive' => true
            ];
        }

        // Check if application deadline has passed
        if ($jobOffer->getDeadline() && $jobOffer->getDeadline() < new \DateTime()) {
            return [
                'success' => false,
                'error' => 'The application deadline for this job has passed',
                'deadline_passed' => true
            ];
        }

        // Create new job application
        $jobRequest = new JobRequest();
        $jobRequest->setCandidate($user);
        $jobRequest->setJobOffer($jobOffer);
        $jobRequest->setApplicationDate(new \DateTime());
        $jobRequest->setStatus('PENDING');

        // Set additional application data
        if (isset($applicationData['coverLetter'])) {
            $jobRequest->setCoverLetter($applicationData['coverLetter']);
        }

        if (isset($applicationData['cvFile'])) {
            $jobRequest->setCvFile($applicationData['cvFile']);
        }

        if (isset($applicationData['expectedSalary'])) {
            $jobRequest->setExpectedSalary($applicationData['expectedSalary']);
        }

        try {
            $this->entityManager->persist($jobRequest);
            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'Application submitted successfully',
                'application_id' => $jobRequest->getId()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to submit application: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all jobs a user has applied for
     */
    public function getUserApplications(User $user): array
    {
        return $this->jobRequestRepository->findBy(['candidate' => $user], ['applicationDate' => 'DESC']);
    }

    /**
     * Get all applications for a specific job
     */
    public function getJobApplications(JobOffer $jobOffer): array
    {
        return $this->jobRequestRepository->findBy(['jobOffer' => $jobOffer], ['applicationDate' => 'DESC']);
    }

    /**
     * Check if user can apply for a job
     */
    public function canUserApplyForJob(User $user, JobOffer $jobOffer): array
    {
        $checks = [
            'can_apply' => true,
            'reasons' => []
        ];

        // Check if already applied
        if ($this->hasUserAppliedForJob($user, $jobOffer)) {
            $checks['can_apply'] = false;
            $checks['reasons'][] = 'You have already applied for this job';
        }

        // Check if job is active
        if (!$jobOffer->isActive()) {
            $checks['can_apply'] = false;
            $checks['reasons'][] = 'This job is no longer accepting applications';
        }

        // Check deadline
        if ($jobOffer->getDeadline() && $jobOffer->getDeadline() < new \DateTime()) {
            $checks['can_apply'] = false;
            $checks['reasons'][] = 'The application deadline has passed';
        }

        // Check if user is the job poster (HR can't apply to their own jobs)
        if ($jobOffer->getPostedBy() === $user) {
            $checks['can_apply'] = false;
            $checks['reasons'][] = 'You cannot apply to jobs you have posted';
        }

        return $checks;
    }

    /**
     * Get application statistics for a user
     */
    public function getUserApplicationStats(User $user): array
    {
        $applications = $this->getUserApplications($user);
        
        $stats = [
            'total_applications' => count($applications),
            'pending_applications' => 0,
            'approved_applications' => 0,
            'rejected_applications' => 0,
            'interview_scheduled' => 0,
            'recent_applications' => []
        ];

        foreach ($applications as $application) {
            switch ($application->getStatus()) {
                case 'PENDING':
                    $stats['pending_applications']++;
                    break;
                case 'APPROVED':
                    $stats['approved_applications']++;
                    break;
                case 'REJECTED':
                    $stats['rejected_applications']++;
                    break;
            }

            if ($application->getInterviews()->count() > 0) {
                $stats['interview_scheduled']++;
            }
        }

        // Get recent applications (last 5)
        $stats['recent_applications'] = array_slice($applications, 0, 5);

        return $stats;
    }

    /**
     * Get application statistics for a job
     */
    public function getJobApplicationStats(JobOffer $jobOffer): array
    {
        $applications = $this->getJobApplications($jobOffer);
        
        $stats = [
            'total_applications' => count($applications),
            'pending_applications' => 0,
            'approved_applications' => 0,
            'rejected_applications' => 0,
            'interview_scheduled' => 0,
            'applications_by_date' => []
        ];

        foreach ($applications as $application) {
            switch ($application->getStatus()) {
                case 'PENDING':
                    $stats['pending_applications']++;
                    break;
                case 'APPROVED':
                    $stats['approved_applications']++;
                    break;
                case 'REJECTED':
                    $stats['rejected_applications']++;
                    break;
            }

            if ($application->getInterviews()->count() > 0) {
                $stats['interview_scheduled']++;
            }

            // Group by date
            $date = $application->getApplicationDate()->format('Y-m-d');
            if (!isset($stats['applications_by_date'][$date])) {
                $stats['applications_by_date'][$date] = 0;
            }
            $stats['applications_by_date'][$date]++;
        }

        return $stats;
    }

    /**
     * Withdraw an application
     */
    public function withdrawApplication(User $user, JobOffer $jobOffer): array
    {
        $application = $this->jobRequestRepository->findOneBy([
            'candidate' => $user,
            'jobOffer' => $jobOffer
        ]);

        if (!$application) {
            return [
                'success' => false,
                'error' => 'No application found for this job'
            ];
        }

        // Check if application can be withdrawn (not approved or scheduled for interview)
        if (in_array($application->getStatus(), ['APPROVED', 'INTERVIEW_SCHEDULED'])) {
            return [
                'success' => false,
                'error' => 'This application cannot be withdrawn in its current status'
            ];
        }

        try {
            $this->entityManager->remove($application);
            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'Application withdrawn successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to withdraw application: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get recommended jobs for a user based on their skills and application history
     */
    public function getRecommendedJobs(User $user, int $limit = 10): array
    {
        // Get user's applications to understand preferences
        $userApplications = $this->getUserApplications($user);
        $appliedJobIds = array_map(fn($app) => $app->getJobOffer()->getId(), $userApplications);

        // Get active jobs the user hasn't applied for
        $availableJobs = $this->jobOfferRepository->findActiveJobsNotAppliedByUser($user->getId(), $appliedJobIds, $limit);

        return $availableJobs;
    }
}
