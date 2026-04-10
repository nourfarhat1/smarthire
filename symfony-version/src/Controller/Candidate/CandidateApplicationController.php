<?php

namespace App\Controller\Candidate;

use App\Entity\JobOffer;
use App\Entity\JobRequest;
use App\Repository\JobOfferRepository;
use App\Repository\JobRequestRepository;
use App\Repository\TrainingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidate/applications')]
#[IsGranted('ROLE_CANDIDATE')]
class CandidateApplicationController extends AbstractController
{
    public function __construct(
        private JobOfferRepository $jobOfferRepository,
        private JobRequestRepository $jobRequestRepository,
        private TrainingRepository $trainingRepository
    ) {
    }

    #[Route('/', name: 'app_candidate_applications')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $type = $request->query->get('type', 'all');

        // Get all job requests with filtering
        $jobRequests = $this->jobRequestRepository->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'jo')
            ->addSelect('jo')
            ->where('jr.candidate = :user')
            ->setParameter('user', $user)
            ->orderBy('jr.submissionDate', 'DESC')
            ->getQuery()
            ->getResult();

        // Separate regular and spontaneous applications
        $regularApplications = array_filter($jobRequests, fn($jr) => $jr->getJobOffer() !== null);
        $spontaneousApplications = array_filter($jobRequests, fn($jr) => $jr->getJobOffer() === null);

        // Apply filters
        if (!empty($search)) {
            $regularApplications = array_filter($regularApplications, function($jr) use ($search) {
                return stripos($jr->getJobOffer()->getTitle(), $search) !== false ||
                       stripos($jr->getJobOffer()->getDescription(), $search) !== false;
            });
            
            $spontaneousApplications = array_filter($spontaneousApplications, function($jr) use ($search) {
                return stripos($jr->getJobTitle(), $search) !== false ||
                       stripos($jr->getCoverLetter(), $search) !== false;
            });
        }

        if (!empty($status)) {
            $regularApplications = array_filter($regularApplications, function($jr) use ($status) {
                return $jr->getStatus() === $status;
            });
            
            $spontaneousApplications = array_filter($spontaneousApplications, function($jr) use ($status) {
                return $jr->getStatus() === $status;
            });
        }

        // Filter by type
        $filteredRegularApplications = ($type === 'spontaneous') ? [] : $regularApplications;
        $filteredSpontaneousApplications = ($type === 'job') ? [] : $spontaneousApplications;

        // Calculate statistics
        $totalApplications = count($jobRequests);
        $pendingApplications = count(array_filter($jobRequests, fn($jr) => $jr->getStatus() === 'PENDING'));
        $acceptedApplications = count(array_filter($jobRequests, fn($jr) => $jr->getStatus() === 'ACCEPTED'));
        $rejectedApplications = count(array_filter($jobRequests, fn($jr) => $jr->getStatus() === 'REJECTED'));

        return $this->render('candidate/applications/index.html.twig', [
            'jobRequests' => $filteredRegularApplications,
            'spontaneousApplications' => $filteredSpontaneousApplications,
            'totalApplications' => $totalApplications,
            'pendingApplications' => $pendingApplications,
            'acceptedApplications' => $acceptedApplications,
            'rejectedApplications' => $rejectedApplications,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedType' => $type,
        ]);
    }

    #[Route('/job-marketplace', name: 'app_candidate_job_marketplace')]
    public function jobMarketplace(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $location = $request->query->get('location', '');
        $type = $request->query->get('type', '');

        // Get all job offers with filtering
        $qb = $this->jobOfferRepository->createQueryBuilder('jo')
            ->orderBy('jo.postedDate', 'DESC');

        // Apply search filter
        if (!empty($search)) {
            $qb->andWhere('jo.title LIKE :search OR jo.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Apply category filter
        if (!empty($category)) {
            $qb->andWhere('jo.category = :category')
               ->setParameter('category', $category);
        }

        // Apply location filter
        if (!empty($location)) {
            $qb->andWhere('jo.location LIKE :location')
               ->setParameter('location', '%' . $location . '%');
        }

        // Apply type filter
        if (!empty($type)) {
            $qb->andWhere('jo.employmentType = :type')
               ->setParameter('type', $type);
        }

        $jobOffers = $qb->getQuery()->getResult();

        // Get user's saved jobs and applications
        $user = $this->getUser();
        $userApplications = $this->jobRequestRepository->findBy(['candidate' => $user]);
        $appliedJobIds = array_map(fn($jr) => $jr->getJobOffer()?->getId(), $userApplications);
        $appliedJobIds = array_filter($appliedJobIds, fn($id) => $id !== null);

        return $this->render('candidate/applications/marketplace.html.twig', [
            'jobOffers' => $jobOffers,
            'appliedJobIds' => $appliedJobIds,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedLocation' => $location,
            'selectedType' => $type,
        ]);
    }

    #[Route('/apply/{id}', name: 'app_candidate_apply_job', methods: ['GET', 'POST'])]
    public function applyJob(Request $request, int $id, JobOfferRepository $jobOfferRepository): Response
    {
        $user = $this->getUser();
        
        // Find the job offer
        $jobOffer = $jobOfferRepository->find($id);
        if (!$jobOffer) {
            throw $this->createNotFoundException('Job offer not found.');
        }

        // Check if already applied
        $existingApplication = $this->jobRequestRepository->findOneBy(['candidate' => $user, 'jobOffer' => $jobOffer]);
        if ($existingApplication) {
            $this->addFlash('error', 'You have already applied for this job.');
            return $this->redirectToRoute('app_candidate_job_marketplace');
        }

        if ($request->isMethod('POST')) {
            $coverLetter = $request->request->get('coverLetter');
            $expectedSalary = $request->request->get('expectedSalary');

            // Basic validation
            if (empty($coverLetter)) {
                $this->addFlash('error', 'Please provide a cover letter.');
                return $this->redirectToRoute('app_candidate_apply_job', ['id' => $id]);
            }

            // Create new job request
            $jobRequest = new JobRequest();
            $jobRequest->setCandidate($user);
            $jobRequest->setJobOffer($jobOffer);
            $jobRequest->setSubmissionDate(new \DateTime());
            $jobRequest->setStatus('PENDING');
            $jobRequest->setCoverLetter($coverLetter);
            
            if (!empty($expectedSalary)) {
                $jobRequest->setSuggestedSalary($expectedSalary);
            }

            $this->jobRequestRepository->save($jobRequest, true);
            $this->addFlash('success', 'Your application has been submitted successfully!');

            return $this->redirectToRoute('app_candidate_applications');
        }

        return $this->render('candidate/applications/apply.html.twig', [
            'jobOffer' => $jobOffer,
        ]);
    }

    #[Route('/spontaneous', name: 'app_candidate_spontaneous_application', methods: ['GET', 'POST'])]
    public function spontaneousApplication(Request $request): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $subject = $request->request->get('subject');
            $description = $request->request->get('description');
            $category = $request->request->get('category');

            // Basic validation
            if (empty($subject) || empty($description)) {
                $this->addFlash('error', 'Please fill in all required fields.');
                return $this->redirectToRoute('app_candidate_spontaneous_application');
            }

            // Create new spontaneous application (JobRequest without jobOffer)
            $jobRequest = new JobRequest();
            $jobRequest->setCandidate($user);
            $jobRequest->setJobTitle($subject);
            $jobRequest->setCoverLetter($description);
            $jobRequest->setCategorie($category);
            $jobRequest->setSubmissionDate(new \DateTime());
            $jobRequest->setStatus('PENDING');

            $this->jobRequestRepository->save($jobRequest, true);
            $this->addFlash('success', 'Your spontaneous application has been submitted successfully!');

            return $this->redirectToRoute('app_candidate_applications');
        }

        return $this->render('candidate/applications/spontaneous.html.twig');
    }

    #[Route('/edit/{id}', name: 'app_candidate_edit_application', methods: ['GET', 'POST'])]
    public function editApplication(Request $request, int $id, JobRequestRepository $jobRequestRepository): Response
    {
        $user = $this->getUser();
        
        $jobRequest = $jobRequestRepository->find($id);
        if (!$jobRequest || $jobRequest->getCandidate() !== $user) {
            throw $this->createAccessDeniedException('You can only edit your own applications.');
        }

        // Check if application is still pending
        if ($jobRequest->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException('You can only edit pending applications.');
        }

        if ($request->isMethod('POST')) {
            $coverLetter = $request->request->get('coverLetter');
            $expectedSalary = $request->request->get('expectedSalary');

            // Basic validation
            if (empty($coverLetter)) {
                $this->addFlash('error', 'Please provide a cover letter.');
                return $this->redirectToRoute('app_candidate_edit_application', ['id' => $id]);
            }

            // Update job request
            $jobRequest->setCoverLetter($coverLetter);
            
            if (!empty($expectedSalary)) {
                $jobRequest->setSuggestedSalary($expectedSalary);
            }

            $this->jobRequestRepository->save($jobRequest, true);
            $this->addFlash('success', 'Your application has been updated successfully!');

            return $this->redirectToRoute('app_candidate_applications');
        }

        return $this->render('candidate/applications/edit.html.twig', [
            'jobRequest' => $jobRequest,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_candidate_delete_application', methods: ['POST'])]
    public function deleteApplication(Request $request, int $id, JobRequestRepository $jobRequestRepository): Response
    {
        $user = $this->getUser();
        
        $jobRequest = $jobRequestRepository->find($id);
        if (!$jobRequest || $jobRequest->getCandidate() !== $user) {
            throw $this->createAccessDeniedException('You can only delete your own applications.');
        }

        // Check if application is still pending
        if ($jobRequest->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException('You can only delete pending applications.');
        }

        $this->jobRequestRepository->remove($jobRequest, true);
        $this->addFlash('success', 'Your application has been deleted successfully.');

        return $this->redirectToRoute('app_candidate_applications');
    }

    #[Route('/training-recommendations', name: 'app_candidate_training_recommendations')]
    public function trainingRecommendations(): Response
    {
        $user = $this->getUser();
        
        // Get user's job applications to analyze skills
        $jobRequests = $this->jobRequestRepository->findBy(['candidate' => $user]);
        
        // Generate training recommendations based on applied job categories
        $recommendations = [];
        $categories = [];
        
        foreach ($jobRequests as $jobRequest) {
            if ($jobRequest->getJobOffer()) {
                $category = $jobRequest->getJobOffer()->getCategory();
                if (!in_array($category, $categories)) {
                    $categories[] = $category;
                }
            } elseif ($jobRequest->getCategorie()) {
                $category = $jobRequest->getCategorie();
                if (!in_array($category, $categories)) {
                    $categories[] = $category;
                }
            }
        }

        if (!empty($categories)) {
            // Get trainings based on categories
            foreach ($categories as $category) {
                $trainings = $this->trainingRepository->findBy(['category' => $category], ['likes' => 'DESC'], 2);
                $recommendations = array_merge($recommendations, $trainings);
            }
        } else {
            // Get general popular trainings
            $recommendations = $this->trainingRepository->findBy([], ['likes' => 'DESC'], 6);
        }

        // Remove duplicates and limit
        $recommendations = array_unique($recommendations, SORT_REGULAR);
        $recommendations = array_slice($recommendations, 0, 6);

        return $this->render('candidate/applications/training-recommendations.html.twig', [
            'recommendations' => $recommendations,
            'categories' => $categories,
        ]);
    }

    #[Route('/save-job/{id}', name: 'app_candidate_save_job', methods: ['POST'])]
    public function saveJob(Request $request, int $id, JobOfferRepository $jobOfferRepository): Response
    {
        $user = $this->getUser();
        
        $jobOffer = $jobOfferRepository->find($id);
        if (!$jobOffer) {
            throw $this->createNotFoundException('Job offer not found.');
        }

        // This would typically save the job to user's saved jobs list
        // For now, we'll just add a flash message
        $this->addFlash('success', 'Job saved to your favorites!');

        return $this->redirectToRoute('app_candidate_job_marketplace');
    }
}
