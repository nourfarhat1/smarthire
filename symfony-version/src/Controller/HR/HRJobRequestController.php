<?php

namespace App\Controller\HR;

use App\Repository\JobRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hr/job-requests')]
#[IsGranted('ROLE_HR')]
class HRJobRequestController extends AbstractController
{
    public function __construct(
        private JobRequestRepository $jobRequestRepository
    ) {
    }

    #[Route('/', name: 'app_hr_job_requests')]
    public function index(Request $request): Response
    {
        $hrUser = $this->getUser();
        
        // Get filter parameters
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $jobOfferId = $request->query->get('jobOffer', '');
        
        // Build query with filters
        $queryBuilder = $this->jobRequestRepository->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'jo')
            ->leftJoin('jr.candidate', 'c')
            ->where('jo.recruiter = :hrUser')
            ->setParameter('hrUser', $hrUser);
        
        // Apply search filter (search by candidate name or email)
        if (!empty($search)) {
            $queryBuilder->andWhere(
                '(c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search)'
            )->setParameter('search', '%' . $search . '%');
        }
        
        // Apply status filter
        if (!empty($status)) {
            $queryBuilder->andWhere('jr.status = :status')
                ->setParameter('status', $status);
        }
        
        // Apply job offer filter
        if (!empty($jobOfferId)) {
            $queryBuilder->andWhere('jo.id = :jobOfferId')
                ->setParameter('jobOfferId', $jobOfferId);
        }
        
        $jobRequests = $queryBuilder
            ->orderBy('jr.submissionDate', 'DESC')
            ->getQuery()
            ->getResult();

        // Calculate unique job offers count
        $uniqueJobOffers = [];
        foreach ($jobRequests as $jobRequest) {
            if ($jobRequest->getJobOffer()) {
                $uniqueJobOffers[$jobRequest->getJobOffer()->getId()] = $jobRequest->getJobOffer();
            }
        }
        $uniqueJobOffersCount = count($uniqueJobOffers);
        
        // Get all job offers for the filter dropdown
        $allJobOffers = $this->jobRequestRepository->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'jo')
            ->where('jo.recruiter = :hrUser')
            ->setParameter('hrUser', $hrUser)
            ->groupBy('jo.id, jo.title')
            ->orderBy('jo.title', 'ASC')
            ->select('jo.id, jo.title')
            ->getQuery()
            ->getResult();

        return $this->render('hr/job_requests/index.html.twig', [
            'job_requests' => $jobRequests,
            'unique_job_offers_count' => $uniqueJobOffersCount,
            'all_job_offers' => $allJobOffers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'jobOffer' => $jobOfferId,
            ]
        ]);
    }
}
