<?php

namespace App\Controller\HR;

use App\Repository\JobRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function index(): Response
    {
        $hrUser = $this->getUser();
        
        // Get applications for HR's own job offers
        $jobRequests = $this->jobRequestRepository->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'jo')
            ->leftJoin('jr.candidate', 'c')
            ->where('jo.recruiter = :hrUser')
            ->setParameter('hrUser', $hrUser)
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

        return $this->render('hr/job_requests/index.html.twig', [
            'job_requests' => $jobRequests,
            'unique_job_offers_count' => $uniqueJobOffersCount,
        ]);
    }
}
