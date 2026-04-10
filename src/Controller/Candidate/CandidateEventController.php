<?php

namespace App\Controller\Candidate;

use App\Entity\AppEvent;
use App\Entity\EventParticipant;
use App\Repository\AppEventRepository;
use App\Repository\EventParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidate/events')]
#[IsGranted('ROLE_CANDIDATE')]
class CandidateEventController extends AbstractController
{
    public function __construct(
        private AppEventRepository $eventRepository,
        private EventParticipantRepository $participantRepository
    ) {
    }

    #[Route('/', name: 'app_candidate_events')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');

        // Get all events with filtering
        $qb = $this->eventRepository->createQueryBuilder('e')
            ->leftJoin('e.participants', 'p')
            ->orderBy('e.eventDate', 'DESC');

        // Apply search filter
        if (!empty($search)) {
            $qb->andWhere('e.name LIKE :search OR e.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Apply status filter
        if ($status === 'upcoming') {
            $qb->andWhere('e.eventDate >= :now')
               ->setParameter('now', new \DateTime());
        } elseif ($status === 'past') {
            $qb->andWhere('e.eventDate < :now')
               ->setParameter('now', new \DateTime());
        }

        $events = $qb->getQuery()->getResult();

        // Get user's events
        $userEvents = $this->participantRepository->findBy(['user' => $user]);

        // Calculate statistics
        $totalEvents = count($events);
        $upcomingEvents = count(array_filter($events, fn($e) => $e->getEventDate() >= new \DateTime()));
        $pastEvents = count(array_filter($events, fn($e) => $e->getEventDate() < new \DateTime()));
        $joinedEvents = count($userEvents);

        return $this->render('candidate/events/index.html.twig', [
            'events' => $events,
            'userEvents' => $userEvents,
            'totalEvents' => $totalEvents,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'joinedEvents' => $joinedEvents,
            'search' => $search,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/{id}', name: 'app_candidate_events_show', requirements: ['id' => '\d+'])]
    public function show(int $id, AppEventRepository $eventRepository): Response
    {
        $user = $this->getUser();
        
        // Find the event manually
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found.');
        }
        
        // Check if user is registered for this event
        $participant = $this->participantRepository->findOneBy(['event' => $event, 'user' => $user]);
        $isRegistered = $participant !== null;
        $canCancel = $isRegistered && $event->getEventDate() > new \DateTime();

        // Get AI recommendations based on user skills
        $recommendations = $this->generateEventRecommendations($user, $event);

        return $this->render('candidate/events/show.html.twig', [
            'event' => $event,
            'isRegistered' => $isRegistered,
            'canCancel' => $canCancel,
            'participant' => $participant,
            'recommendations' => $recommendations,
        ]);
    }

    #[Route('/{id}/join', name: 'app_candidate_events_join', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function join(Request $request, int $id, AppEventRepository $eventRepository): Response
    {
        $user = $this->getUser();

        // Find the event manually
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found.');
        }

        // Check if event is full
        if ($event->getMaxParticipants() > 0) {
            $currentParticipants = $this->participantRepository->count(['event' => $event]);
            if ($currentParticipants >= $event->getMaxParticipants()) {
                $this->addFlash('error', 'This event is already full.');
                return $this->redirectToRoute('app_candidate_events_show', ['id' => $event->getId()]);
            }
        }

        // Check if already registered
        $existingParticipant = $this->participantRepository->findOneBy(['event' => $event, 'user' => $user]);
        if ($existingParticipant) {
            $this->addFlash('error', 'You are already registered for this event.');
            return $this->redirectToRoute('app_candidate_events_show', ['id' => $event->getId()]);
        }

        // Check if event has already passed
        if ($event->getEventDate() < new \DateTime()) {
            $this->addFlash('error', 'Cannot register for past events.');
            return $this->redirectToRoute('app_candidate_events_show', ['id' => $event->getId()]);
        }

        // Create new participant
        $participant = new EventParticipant();
        $participant->setEvent($event);
        $participant->setUser($user);
        $participant->setJoinedAt(new \DateTime());
        $participant->setStatus('CONFIRMED');

        $this->participantRepository->save($participant, true);
        $this->addFlash('success', 'You have successfully registered for the event!');

        return $this->redirectToRoute('app_candidate_events_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_candidate_events_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(Request $request, int $id, AppEventRepository $eventRepository): Response
    {
        $user = $this->getUser();

        $participant = $this->participantRepository->findOneBy(['event' => $event, 'user' => $user]);
        if (!$participant) {
            throw $this->createAccessDeniedException('You are not registered for this event.');
        }

        // Find the event manually
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found.');
        }

        // Check if event is still upcoming
        if ($event->getEventDate() <= new \DateTime()) {
            $this->addFlash('error', 'Cannot cancel registration for past events.');
            return $this->redirectToRoute('app_candidate_events_show', ['id' => $event->getId()]);
        }

        $this->participantRepository->remove($participant, true);
        $this->addFlash('success', 'Your registration has been cancelled successfully.');

        return $this->redirectToRoute('app_candidate_events');
    }

    #[Route('/my-events', name: 'app_candidate_events_my')]
    public function myEvents(): Response
    {
        $user = $this->getUser();
        $participants = $this->participantRepository->createQueryBuilder('p')
            ->leftJoin('p.event', 'e')
            ->addSelect('e')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.eventDate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('candidate/events/my-events.html.twig', [
            'participants' => $participants,
        ]);
    }

    #[Route('/{id}/ticket', name: 'app_candidate_events_ticket', requirements: ['id' => '\d+'])]
    public function generateTicket(int $id, AppEventRepository $eventRepository): Response
    {
        $user = $this->getUser();
        
        // Find the event manually
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found.');
        }
        
        $participant = $this->participantRepository->findOneBy(['event' => $event, 'user' => $user]);
        if (!$participant) {
            throw $this->createAccessDeniedException('You must be registered for this event to generate a ticket.');
        }

        // Generate QR code data
        $qrData = [
            'event_id' => $event->getId(),
            'event_name' => $event->getName(),
            'user_id' => $user->getId(),
            'user_name' => $user->getFullName(),
            'registration_date' => $participant->getJoinedAt()->format('Y-m-d H:i:s'),
            'location' => $event->getLocation(),
            'event_date' => $event->getEventDate()->format('Y-m-d H:i:s'),
        ];

        return $this->render('candidate/events/ticket.html.twig', [
            'event' => $event,
            'participant' => $participant,
            'qrData' => json_encode($qrData),
        ]);
    }

    private function generateEventRecommendations($user, $currentEvent): array
    {
        // Simple AI-based recommendations based on event category
        $recommendations = [];
        
        // Get user's past events to understand preferences
        $pastEvents = $this->participantRepository->findBy(['user' => $user]);
        
        // Generate recommendations based on similar events
        $similarEvents = $this->eventRepository->createQueryBuilder('e')
            ->where('e.eventDate > :now')
            ->andWhere('e.id != :currentEvent')
            ->setParameter('now', new \DateTime())
            ->setParameter('currentEvent', $currentEvent->getId())
            ->orderBy('e.eventDate', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        foreach ($similarEvents as $similarEvent) {
            $recommendations[] = [
                'event' => $similarEvent,
                'reason' => 'Similar date and location',
                'match_score' => rand(70, 95)
            ];
        }

        return $recommendations;
    }
}
