<?php

namespace App\Controller\Admin;

use App\Entity\AppEvent;
use App\Entity\EventParticipant;
use App\Repository\AppEventRepository;
use App\Repository\EventParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class EventManagementController extends AbstractController
{
    public function __construct(
        private AppEventRepository $eventRepository,
        private EventParticipantRepository $participantRepository,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/', name: 'app_admin_events')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $month = $request->query->get('month', '');

        // Get all events with filtering
        $events = $this->eventRepository->searchEvents($search, $month, $status);

        // Calculate statistics
        $allEvents = $this->eventRepository->findAll();
        $upcomingEvents = array_filter($allEvents, fn($e) => $e->getEventDate() >= new \DateTime());
        $pastEvents = array_filter($allEvents, fn($e) => $e->getEventDate() < new \DateTime());
        $todayEvents = array_filter($allEvents, fn($e) => $e->getEventDate()->format('Y-m-d') === (new \DateTime())->format('Y-m-d'));

        $stats = [
            'total' => count($allEvents),
            'upcoming' => count($upcomingEvents),
            'past' => count($pastEvents),
            'today' => count($todayEvents),
            'total_participants' => array_sum(array_map(fn($e) => $e->getParticipants()->count(), $allEvents))
        ];

        return $this->render('admin/events/index.html.twig', [
            'events' => $events,
            'stats' => $stats,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedMonth' => $month
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_events_delete', methods: ['POST'])]
    public function delete(Request $request, AppEvent $event): Response
    {
        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $this->eventRepository->remove($event, true);
            $this->addFlash('success', 'Event deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_events');
    }

    #[Route('/{id}/participants', name: 'app_admin_events_participants')]
    public function participants(AppEvent $event): Response
    {
        $participants = $this->participantRepository->findBy(['event' => $event], ['joinedAt' => 'ASC']);

        return $this->render('admin/events/participants.html.twig', [
            'event' => $event,
            'participants' => $participants
        ]);
    }

    #[Route('/{id}/participants/{participantId}/remove', name: 'app_admin_events_remove_participant', methods: ['POST'])]
    public function removeParticipant(Request $request, EventParticipant $participant): Response
    {
        $eventId = $participant->getEvent()->getId();
        if ($this->isCsrfTokenValid('remove_participant' . $participant->getId(), $request->request->get('_token'))) {
            $this->participantRepository->remove($participant, true);
            $this->addFlash('success', 'Participant removed successfully!');
        }

        return $this->redirectToRoute('app_admin_events_participants', ['id' => $eventId]);
    }

    #[Route('/analytics', name: 'app_admin_events_analytics')]
    public function analytics(): Response
    {
        $allEvents = $this->eventRepository->findAll();
        $allParticipants = $this->participantRepository->findAll();

        // Calculate comprehensive analytics
        $analytics = [
            'total_events' => count($allEvents),
            'total_participants' => count($allParticipants),
            'by_status' => [
                'upcoming' => count(array_filter($allEvents, fn($e) => $e->getEventDate() >= new \DateTime())),
                'past' => count(array_filter($allEvents, fn($e) => $e->getEventDate() < new \DateTime())),
                'today' => count(array_filter($allEvents, fn($e) => $e->getEventDate()->format('Y-m-d') === (new \DateTime())->format('Y-m-d')))
            ],
            'monthly_events' => [],
            'popular_events' => [],
            'participation_rates' => []
        ];

        // Calculate monthly trends (last 6 months)
        for ($i = 5; $i >= 0; $i--) {
            $month = (new \DateTime("-$i months"))->format('Y-m');
            $monthEvents = array_filter($allEvents, fn($e) => $e->getEventDate()->format('Y-m') === $month);
            $analytics['monthly_events'][$month] = count($monthEvents);
        }

        // Calculate popular events (by participant count)
        $eventParticipants = [];
        foreach ($allEvents as $event) {
            $eventParticipants[$event->getId()] = [
                'name' => $event->getName(),
                'participants' => $event->getParticipants()->count(),
                'maxParticipants' => $event->getMaxParticipants()
            ];
        }
        uasort($eventParticipants, fn($a, $b) => $b['participants'] <=> $a['participants']);
        $analytics['popular_events'] = array_slice($eventParticipants, 0, 5, true);

        // Calculate participation rates
        foreach ($allEvents as $event) {
            $max = $event->getMaxParticipants();
            $current = $event->getParticipants()->count();
            $rate = $max > 0 ? round(($current / $max) * 100, 1) : 0;
            $analytics['participation_rates'][] = [
                'name' => $event->getName(),
                'rate' => $rate,
                'current' => $current,
                'max' => $max
            ];
        }

        return $this->render('admin/events/analytics.html.twig', [
            'analytics' => $analytics
        ]);
    }

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($id, $token));
    }
}
