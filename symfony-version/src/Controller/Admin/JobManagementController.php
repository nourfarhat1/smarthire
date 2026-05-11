<?php

namespace App\Controller\Admin;

use App\Entity\JobOffer;
use App\Entity\JobCategory;
use App\Form\JobOfferType;
use App\Repository\JobOfferRepository;
use App\Repository\JobCategoryRepository;
use App\Repository\JobRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

#[Route('/admin/jobs')]
#[IsGranted('ROLE_ADMIN')]
class JobManagementController extends AbstractController
{
    public function __construct(
        private JobOfferRepository $jobOfferRepository,
        private JobCategoryRepository $jobCategoryRepository,
        private JobRequestRepository $jobRequestRepository,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/', name: 'app_admin_jobs')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        
        // Get all jobs with filtering
        $jobs = $this->jobOfferRepository->searchJobs($search, $type);
        
        // Get marketplace statistics
        $totalJobs = count($jobs);
        
        // Get jobs by recruiter
        $recruiterStats = [];
        foreach ($jobs as $job) {
            $recruiterId = $job->getRecruiter()?->getId() ?? 'unknown';
            if (!isset($recruiterStats[$recruiterId])) {
                $recruiterStats[$recruiterId] = ['name' => $job->getRecruiter()?->getFullName() ?? 'Unknown', 'count' => 0];
            }
            $recruiterStats[$recruiterId]['count']++;
        }
        
        return $this->render('admin/jobs/index.html.twig', [
            'jobs' => $jobs,
            'stats' => [
                'total' => $totalJobs,
                'recruiters' => $recruiterStats
            ],
            'search' => $search,
            'selectedType' => $type
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_jobs_delete', methods: ['POST'])]
    public function delete(Request $request, JobOffer $job): Response
    {
        if ($this->isCsrfTokenValid('delete' . $job->getId(), $request->request->get('_token'))) {
            $this->jobOfferRepository->remove($job, true);
            $this->addFlash('success', 'Job offer deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_jobs');
    }

    #[Route('/categories', name: 'app_admin_job_categories')]
    public function categories(): Response
    {
        $categories = $this->jobCategoryRepository->findAll();
        
        return $this->render('admin/jobs/categories.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/add', name: 'app_admin_job_categories_add', methods: ['POST'])]
    public function addCategory(Request $request): Response
    {
        $name = $request->request->get('name');
        
        if (empty($name)) {
            $this->addFlash('error', 'Category name is required.');
            return $this->redirectToRoute('app_admin_job_categories');
        }
        
        // Check if category already exists
        $existingCategory = $this->jobCategoryRepository->findOneBy(['name' => $name]);
        if ($existingCategory) {
            $this->addFlash('error', 'Category with this name already exists.');
            return $this->redirectToRoute('app_admin_job_categories');
        }
        
        $category = new JobCategory();
        $category->setName($name);
        
        $this->jobCategoryRepository->save($category, true);
        
        $this->addFlash('success', 'Category added successfully.');
        return $this->redirectToRoute('app_admin_job_categories');
    }

    #[Route('/categories/edit/{id}', name: 'app_admin_job_categories_edit', methods: ['POST'])]
    public function editCategory(Request $request, int $id): Response
    {
        $category = $this->jobCategoryRepository->find($id);
        if (!$category) {
            $this->addFlash('error', 'Category not found.');
            return $this->redirectToRoute('app_admin_job_categories');
        }
        
        $name = $request->request->get('name');
        
        if (empty($name)) {
            $this->addFlash('error', 'Category name is required.');
            return $this->redirectToRoute('app_admin_job_categories');
        }
        
        // Check if another category already has this name
        $existingCategory = $this->jobCategoryRepository->findOneBy(['name' => $name]);
        if ($existingCategory && $existingCategory->getId() !== $id) {
            $this->addFlash('error', 'Category with this name already exists.');
            return $this->redirectToRoute('app_admin_job_categories');
        }
        
        $category->setName($name);
        $this->jobCategoryRepository->save($category, true);
        
        $this->addFlash('success', 'Category updated successfully.');
        return $this->redirectToRoute('app_admin_job_categories');
    }

    #[Route('/categories/delete/{id}', name: 'app_admin_job_categories_delete', methods: ['POST'])]
    public function deleteCategory(Request $request, int $id): Response
    {
        $category = $this->jobCategoryRepository->find($id);
        if (!$category) {
            $this->addFlash('error', 'Category not found.');
            return $this->redirectToRoute('app_admin_job_categories');
        }
        
        try {
            // Check if category has associated jobs
            $jobOffers = $category->getJobOffers();
            if ($jobOffers->count() > 0) {
                $jobCount = $jobOffers->count();
                $jobTitles = [];
                foreach ($jobOffers as $jobOffer) {
                    $jobTitles[] = $jobOffer->getTitle();
                }
                
                $this->addFlash('error', sprintf(
                    'Cannot delete category "%s". It has %d associated job offer(s): %s. Please delete or reassign these job offers first.',
                    $category->getName(),
                    $jobCount,
                    implode(', ', array_slice($jobTitles, 0, 3)) . ($jobCount > 3 ? '...' : '')
                ));
                return $this->redirectToRoute('app_admin_job_categories');
            }
            
            // Attempt to delete the category
            $this->jobCategoryRepository->remove($category, true);
            
            $this->addFlash('success', sprintf('Category "%s" deleted successfully.', $category->getName()));
            
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            $this->addFlash('error', 'Cannot delete category. It is referenced by other records in the database. Please remove all associated job offers and applications first.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while trying to delete the category: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_admin_job_categories');
    }

    #[Route('/categories/force-delete/{id}', name: 'app_admin_job_categories_force_delete', methods: ['POST'])]
    public function forceDeleteCategory(Request $request, int $id): Response
    {
        $category = $this->jobCategoryRepository->find($id);
        if (!$category) {
            $this->addFlash('error', 'Category not found.');
            return $this->redirectToRoute('app_admin_job_categories');
        }
        
        try {
            // Get all job offers for this category
            $jobOffers = $category->getJobOffers();
            
            // Delete all job requests associated with these job offers
            foreach ($jobOffers as $jobOffer) {
                $jobRequests = $this->jobRequestRepository->findBy(['jobOffer' => $jobOffer]);
                foreach ($jobRequests as $jobRequest) {
                    $this->jobRequestRepository->remove($jobRequest, true);
                }
            }
            
            // Delete all job offers
            foreach ($jobOffers as $jobOffer) {
                $this->jobOfferRepository->remove($jobOffer, true);
            }
            
            // Now delete the category
            $this->jobCategoryRepository->remove($category, true);
            
            $this->addFlash('success', sprintf(
                'Category "%s" and all its associated data (%d job offers and their applications) have been deleted.',
                $category->getName(),
                count($jobOffers)
            ));
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while trying to delete the category: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_admin_job_categories');
    }

    #[Route('/applications', name: 'app_admin_applications')]
    public function applications(Request $request): Response
    {
        $status = $request->query->get('status', '');
        $search = $request->query->get('search', '');
        $jobOfferId = $request->query->get('job_offer', '');
        
        // Build query for filtering
        $qb = $this->jobRequestRepository->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'jo')
            ->leftJoin('jr.candidate', 'c')
            ->orderBy('jr.submissionDate', 'DESC');
        
        // Apply status filter
        if (!empty($status)) {
            $qb->andWhere('jr.status = :status')
               ->setParameter('status', $status);
        }
        
        // Apply search filter (search by candidate name, email, or job title)
        if (!empty($search)) {
            $qb->andWhere('c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search OR jo.title LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Apply job offer filter
        if (!empty($jobOfferId)) {
            $qb->andWhere('jo.id = :jobOfferId')
               ->setParameter('jobOfferId', $jobOfferId);
        }
        
        $applications = $qb->getQuery()->getResult();
        
        // Get statistics
        $totalApplications = count($applications);
        $pendingApplications = count(array_filter($applications, fn($app) => $app->getStatus() === 'PENDING'));
        $acceptedApplications = count(array_filter($applications, fn($app) => $app->getStatus() === 'ACCEPTED'));
        $rejectedApplications = count(array_filter($applications, fn($app) => $app->getStatus() === 'REJECTED'));
        
        return $this->render('admin/jobs/applications.html.twig', [
            'applications' => $applications,
            'all_job_offers' => $this->jobOfferRepository->findAll(),
            'stats' => [
                'total' => $totalApplications,
                'pending' => $pendingApplications,
                'accepted' => $acceptedApplications,
                'rejected' => $rejectedApplications
            ],
            'filters' => [
                'status' => $status,
                'search' => $search,
                'job_offer' => $jobOfferId
            ]
        ]);
    }

    #[Route('/applications/{id}/view', name: 'app_admin_application_view')]
    public function viewApplication($id): Response
    {
        $application = $this->jobRequestRepository->find($id);
        if (!$application) {
            $this->addFlash('error', 'Application not found');
            return $this->redirectToRoute('app_admin_applications');
        }

        return $this->render('admin/jobs/application_view.html.twig', [
            'application' => $application
        ]);
    }

    #[Route('/applications/{id}/status', name: 'app_admin_application_status', methods: ['POST'])]
    public function updateApplicationStatus(Request $request, $id): Response
    {
        $application = $this->jobRequestRepository->find($id);
        if (!$application) {
            $this->addFlash('error', 'Application not found');
            return $this->redirectToRoute('app_admin_applications');
        }

        $status = $request->request->get('status');
        if (in_array($status, ['PENDING', 'ACCEPTED', 'REJECTED'])) {
            $application->setStatus($status);
            $this->jobRequestRepository->save($application, true);
            $this->addFlash('success', 'Application status updated successfully');
        } else {
            $this->addFlash('error', 'Invalid status');
        }

        return $this->redirectToRoute('app_admin_applications');
    }

    #[Route('/applications/analytics', name: 'app_admin_applications_analytics')]
    public function applicationAnalytics(): Response
    {
        $applications = $this->jobRequestRepository->findAll();
        
        // Group by month for trend analysis
        $monthlyStats = [];
        foreach ($applications as $app) {
            $month = $app->getSubmissionDate()->format('Y-m');
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = ['total' => 0, 'accepted' => 0, 'rejected' => 0];
            }
            $monthlyStats[$month]['total']++;
            if ($app->getStatus() === 'ACCEPTED') $monthlyStats[$month]['accepted']++;
            if ($app->getStatus() === 'REJECTED') $monthlyStats[$month]['rejected']++;
        }

        // Get job offer performance
        $jobStats = [];
        foreach ($this->jobOfferRepository->findAll() as $job) {
            $jobApplications = array_filter($applications, fn($app) => $app->getJobOffer() && $app->getJobOffer()->getId() === $job->getId());
            $jobStats[$job->getId()] = [
                'title' => $job->getTitle(),
                'total' => count($jobApplications),
                'accepted' => count(array_filter($jobApplications, fn($app) => $app->getStatus() === 'ACCEPTED'))
            ];
        }

        return $this->render('admin/jobs/analytics.html.twig', [
            'monthlyStats' => $monthlyStats,
            'jobStats' => $jobStats,
            'totalApplications' => count($applications)
        ]);
    }

    #[Route('/marketplace/analytics', name: 'app_admin_jobs_marketplace_analytics')]
    public function marketplaceAnalytics(): Response
    {
        $jobs = $this->jobOfferRepository->findAll();
        $applications = $this->jobRequestRepository->findAll();
        
        // Monthly job posting trends
        $monthlyJobStats = [];
        foreach ($jobs as $job) {
            $month = $job->getPostedDate()->format('Y-m');
            if (!isset($monthlyJobStats[$month])) {
                $monthlyJobStats[$month] = ['posted' => 0, 'active' => 0];
            }
            $monthlyJobStats[$month]['posted']++;
            if ($job->isActive()) $monthlyJobStats[$month]['active']++;
        }
        
        // Job categories performance
        $categoryStats = [];
        foreach ($jobs as $job) {
            $category = $job->getCategoryName() ?? 'Uncategorized';
            if (!isset($categoryStats[$category])) {
                $categoryStats[$category] = ['total' => 0, 'applications' => 0];
            }
            $categoryStats[$category]['total']++;
        }
        
        // Count applications per category
        foreach ($applications as $app) {
            if ($app->getJobOffer()) {
                $category = $app->getJobOffer()->getCategoryName() ?? 'Uncategorized';
                if (isset($categoryStats[$category])) {
                    $categoryStats[$category]['applications']++;
                }
            }
        }
        
        // Top performing jobs
        $jobPerformance = [];
        foreach ($jobs as $job) {
            $jobApplications = array_filter($applications, fn($app) => $app->getJobOffer()?->getId() === $job->getId());
            $jobPerformance[$job->getId()] = [
                'title' => $job->getTitle(),
                'recruiter' => $job->getRecruiter()?->getFullName() ?? 'Unknown',
                'applications' => count($jobApplications),
                'category' => $job->getCategoryName() ?? 'Uncategorized'
            ];
        }
        
        return $this->render('admin/jobs/marketplace_analytics.html.twig', [
            'monthlyJobStats' => $monthlyJobStats,
            'categoryStats' => $categoryStats,
            'jobPerformance' => $jobPerformance,
            'totalJobs' => count($jobs),
            'totalApplications' => count($applications)
        ]);
    }

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($id, $token));
    }
}
