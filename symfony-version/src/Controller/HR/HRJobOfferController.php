<?php

namespace App\Controller\HR;

use App\Entity\JobOffer;
use App\Entity\JobCategory;
use App\Repository\JobOfferRepository;
use App\Repository\JobCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hr/job-offers')]
#[IsGranted('ROLE_HR')]
class HRJobOfferController extends AbstractController
{
    public function __construct(
        private JobOfferRepository $jobOfferRepository,
        private JobCategoryRepository $jobCategoryRepository
    ) {
    }

    #[Route('/', name: 'app_hr_job_offers')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $jobType = $request->query->get('jobType', '');

        // Get HR user job offers with filtering
        $qb = $this->jobOfferRepository->createQueryBuilder('j')
            ->leftJoin('j.category', 'c')
            ->where('j.recruiter = :user')
            ->setParameter('user', $user)
            ->orderBy('j.postedDate', 'DESC');

        // Apply search filter
        if (!empty($search)) {
            $qb->andWhere('j.title LIKE :search OR j.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Apply category filter
        if (!empty($category)) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', $category);
        }

        // Apply job type filter
        if (!empty($jobType)) {
            $qb->andWhere('j.jobType = :jobType')
               ->setParameter('jobType', $jobType);
        }

        $jobOffers = $qb->getQuery()->getResult();

        // Get job categories for filter dropdown
        $jobCategories = $this->jobCategoryRepository->findAll();

        // Calculate statistics
        $totalCount = count($jobOffers);
        $activeCount = count(array_filter($jobOffers, fn($j) => $j->getPostedDate() >= new \DateTime('-30 days')));
        $totalApplications = array_sum(array_map(fn($j) => $j->getJobRequests()->count(), $jobOffers));

        return $this->render('hr/job_offers/index.html.twig', [
            'jobOffers' => $jobOffers,
            'jobCategories' => $jobCategories,
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'totalApplications' => $totalApplications,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedJobType' => $jobType,
        ]);
    }

    #[Route('/new', name: 'app_hr_job_offers_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        $jobOffer = new JobOffer();
        $jobOffer->setRecruiter($user);
        $jobOffer->setPostedDate(new \DateTime());
        
        $categories = $this->jobCategoryRepository->findAll();
        $jobTypes = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote', 'Hybrid'];
        
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $location = $request->request->get('location');
            $salaryRange = $request->request->get('salaryRange');
            $jobType = $request->request->get('jobType');
            $categoryId = $request->request->get('category');
            
            // Basic validation
            if (empty($title) || empty($description) || empty($location) || empty($jobType) || empty($categoryId)) {
                $this->addFlash('error', 'Please fill in all required fields.');
                return $this->render('hr/job_offers/new.html.twig', [
                    'categories' => $categories,
                    'jobTypes' => $jobTypes,
                ]);
            }
            
            if (strlen($description) < 50) {
                $this->addFlash('error', 'Description must be at least 50 characters.');
                return $this->render('hr/job_offers/new.html.twig', [
                    'categories' => $categories,
                    'jobTypes' => $jobTypes,
                ]);
            }
            
            // Set job offer data
            $jobOffer->setTitle($title);
            $jobOffer->setDescription($description);
            $jobOffer->setLocation($location);
            $jobOffer->setSalaryRange($salaryRange);
            $jobOffer->setJobType($jobType);
            
            // Set job category
            $category = $this->jobCategoryRepository->find($categoryId);
            if ($category) {
                $jobOffer->setCategory($category);
            }

            // Save to database
            $this->jobOfferRepository->save($jobOffer, true);
            $this->addFlash('success', 'Job offer has been created successfully!');

            return $this->redirectToRoute('app_hr_job_offers');
        }

        return $this->render('hr/job_offers/new.html.twig', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_hr_job_offers_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JobOffer $jobOffer): Response
    {
        // Check if the user owns this job offer
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own job offers.');
        }

        $categories = $this->jobCategoryRepository->findAll();
        $jobTypes = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote', 'Hybrid'];

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $location = $request->request->get('location');
            $salaryRange = $request->request->get('salaryRange');
            $jobType = $request->request->get('jobType');
            $categoryId = $request->request->get('category');
            
            // Basic validation
            if (empty($title) || empty($description) || empty($location) || empty($jobType) || empty($categoryId)) {
                $this->addFlash('error', 'Please fill in all required fields.');
                return $this->redirectToRoute('app_hr_job_offers_edit', ['id' => $jobOffer->getId()]);
            }
            
            if (strlen($description) < 50) {
                $this->addFlash('error', 'Description must be at least 50 characters.');
                return $this->redirectToRoute('app_hr_job_offers_edit', ['id' => $jobOffer->getId()]);
            }
            
            // Update job offer data
            $jobOffer->setTitle($title);
            $jobOffer->setDescription($description);
            $jobOffer->setLocation($location);
            $jobOffer->setSalaryRange($salaryRange);
            $jobOffer->setJobType($jobType);
            
            // Update job category
            $category = $this->jobCategoryRepository->find($categoryId);
            if ($category) {
                $jobOffer->setCategory($category);
            }

            // Save to database
            $this->jobOfferRepository->save($jobOffer, true);
            $this->addFlash('success', 'Job offer has been updated successfully!');

            return $this->redirectToRoute('app_hr_job_offers');
        }

        return $this->render('hr/job_offers/edit.html.twig', [
            'jobOffer' => $jobOffer,
            'categories' => $categories,
            'jobTypes' => $jobTypes,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_hr_job_offers_delete', methods: ['POST'])]
    public function delete(Request $request, JobOffer $jobOffer): Response
    {
        // Check if the user owns this job offer
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own job offers.');
        }

        $this->jobOfferRepository->remove($jobOffer, true);
        $this->addFlash('success', 'Job offer has been deleted successfully!');

        return $this->redirectToRoute('app_hr_job_offers');
    }

    #[Route('/categories', name: 'app_hr_job_categories')]
    public function categories(): Response
    {
        $categories = $this->jobCategoryRepository->findAll();
        
        return $this->render('hr/job_offers/categories.html.twig', [
            'categories' => $categories,
        ]);
    }
}
