<?php

namespace App\Controller\HR;

use App\Repository\JobOfferRepository;
use App\Repository\JobRequestRepository;
use App\Repository\AppEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hr')]
class HRDashboardController extends AbstractController
{
    public function __construct(
        private JobOfferRepository $jobOfferRepository,
        private JobRequestRepository $jobRequestRepository,
        private AppEventRepository $eventRepository
    ) {
    }

    #[Route('/', name: 'app_hr_dashboard')]
    public function dashboard(): Response
    {
        // Get HR user object
        $hrUser = $this->getUser();
        
        // Get active offers for this HR (using recruiter relationship)
        $activeOffers = count($this->jobOfferRepository->findBy(['recruiter' => $hrUser]));
        
        // Get applications for this HR's job offers
        $totalApplications = count($this->jobRequestRepository->findApplicationsForHR($hrUser->getId()));
        $pendingApplications = count($this->jobRequestRepository->findPendingApplicationsForHR($hrUser->getId()));
        
        // Get events organized by this HR (using organizer relationship)
        $myEvents = count($this->eventRepository->findBy(['organizer' => $hrUser]));

        // Get recent applications
        $recentApplications = $this->jobRequestRepository->findRecentApplicationsForHR($hrUser->getId(), 5);

        return $this->render('hr/dashboard.html.twig', [
            'stats' => [
                'active_offers' => $activeOffers,
                'total_applications' => $totalApplications,
                'pending_applications' => $pendingApplications,
                'my_events' => $myEvents,
            ],
            'recent_applications' => $recentApplications,
        ]);
    }
}
