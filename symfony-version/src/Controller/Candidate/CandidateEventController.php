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
use App\Service\AIService;
use App\Service\WeatherService;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;


#[Route('/candidate/events')]
#[IsGranted('ROLE_CANDIDATE')]
class CandidateEventController extends AbstractController
{
    public function __construct(
        private AppEventRepository $eventRepository,
        private EventParticipantRepository $participantRepository,
        private AIService $aiService 
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
public function show(int $id, AIService $aiService, WeatherService $weatherService): Response
{
    $user = $this->getUser();
    
    $event = $this->eventRepository->find($id);
    if (!$event) {
        throw $this->createNotFoundException('Event not found.');
    }
    
    $participant = $this->participantRepository->findOneBy(['event' => $event, 'user' => $user]);
    $isRegistered = $participant !== null;
    $canCancel = $isRegistered && $event->getEventDate() > new \DateTime();

    // Recommandations IA
    $recommendations = $this->generateEventRecommendations($user, $event, $aiService);
    
    // 🔥 METEO DIRECTEMENT AVEC LA LOCATION (pas besoin d'extraire) 🔥
    $weather = $weatherService->getWeather($event->getLocation());

    return $this->render('candidate/events/show.html.twig', [
        'event' => $event,
        'isRegistered' => $isRegistered,
        'canCancel' => $canCancel,
        'participant' => $participant,
        'recommendations' => $recommendations,
        'weather' => $weather
    ]);
}

/**
 * Extrait le nom de la ville à partir de la localisation de l'événement
 */

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
public function generateTicket(int $id): Response
{
    $user = $this->getUser();
    $event = $this->eventRepository->find($id);
    
    if (!$event) {
        throw $this->createNotFoundException('Event not found.');
    }
    
    $participant = $this->participantRepository->findOneBy(['event' => $event, 'user' => $user]);
    if (!$participant) {
        throw $this->createAccessDeniedException('You must be registered for this event to generate a ticket.');
    }

    // Données pour le QR code
    $qrData = [
        'event_id' => $event->getId(),
        'event_name' => $event->getName(),
        'user_id' => $user->getId(),
        'user_name' => $user->getFullName(),
        'user_email' => $user->getEmail(),
        'registration_date' => $participant->getJoinedAt()->format('Y-m-d H:i:s'),
        'location' => $event->getLocation(),
        'event_date' => $event->getEventDate()->format('Y-m-d H:i:s'),
    ];
    
    // URL de l'API QR code gratuite
    $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode(json_encode($qrData)) . '&size=250&margin=2';

    return $this->render('candidate/events/ticket.html.twig', [
        'event' => $event,
        'participant' => $participant,
        'qrData' => json_encode($qrData),
        'qrCodeUrl' => $qrCodeUrl,
    ]);
}
  private function generateEventRecommendations($user, $currentEvent): array
{
    // Vérifier si l'utilisateur a des compétences
    if (!$user->getSkills()) {
        return [];
    }
    
    $recommendations = [];
    
    // Récupérer les autres événements à venir
    $otherEvents = $this->eventRepository->createQueryBuilder('e')
        ->where('e.eventDate > :now')
        ->andWhere('e.id != :currentEventId')
        ->setParameter('now', new \DateTime())
        ->setParameter('currentEventId', $currentEvent->getId())
        ->orderBy('e.eventDate', 'ASC')
        ->setMaxResults(5)  // Limiter pour éviter rate limit
        ->getQuery()
        ->getResult();

    foreach ($otherEvents as $event) {
        try {
            // Utiliser l'IA pour calculer le vrai score de matching
            $result = $this->aiService->calculateEventMatching(
                $user->getSkillsString(),
                $event->getDescription()
            );
            
            $recommendations[] = [
                'event' => $event,
                'reason' => $result['recommendation'],  // La recommandation de l'IA
                'match_score' => $result['score']       // Le vrai score (0-100)
            ];
            
            // Petit délai pour éviter rate limit
            usleep(500000); // 0.5 secondes
            
        } catch (\Exception $e) {
            // En cas d'erreur API, on passe à l'événement suivant
            continue;
        }
    }
    
    // Trier par score décroissant (les meilleurs en premier)
    usort($recommendations, function($a, $b) {
        return $b['match_score'] <=> $a['match_score'];
    });
    
    // Retourner les 3 meilleures recommandations
    return array_slice($recommendations, 0, 3);
}




    /**
 * Affiche les événements triés par matching avec les compétences du candidat
 */

#[Route('/matching', name: 'app_candidate_events_matching')]
public function matchingEvents(AIService $aiService): Response
{
    $user = $this->getUser();
    
    if (!$user->getSkills()) {
        $this->addFlash('warning', 'Please upload your CV first to get personalized event matches.');
        return $this->redirectToRoute('app_candidate_profile_edit');
    }
    
    // Limiter à seulement 5 événements pour éviter le rate limit
    $events = $this->eventRepository->createQueryBuilder('e')
        ->where('e.eventDate >= :now')
        ->setParameter('now', new \DateTime())
        ->orderBy('e.eventDate', 'ASC')
        ->setMaxResults(5)  // LIMITE À 5 ÉVÉNEMENTS
        ->getQuery()
        ->getResult();
    
    $matches = [];
    
    foreach ($events as $event) {
        try {
            $result = $aiService->calculateEventMatching(
                $user->getSkillsString(),
                $event->getDescription()
            );
            
            $matches[] = [
                'event' => $event,
                'score' => $result['score'],
                'recommendation' => $result['recommendation']
            ];
            
            // Attendre 2 secondes entre chaque requête
            sleep(2);
            
        } catch (\Exception $e) {
            // En cas d'erreur, mettre un score par défaut
            $matches[] = [
                'event' => $event,
                'score' => 0,
                'recommendation' => 'Unable to calculate match score. Please try again later.'
            ];
        }
    }
    
    usort($matches, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return $this->render('candidate/events/matching.html.twig', [
        'matches' => $matches,
        'userSkills' => $user->getSkillsArray()
    ]);
}


/**
 * Affiche le matching score pour un événement spécifique
 */
#[Route('/{id}/matching-score', name: 'app_candidate_events_matching_score', methods: ['GET'])]
public function matchingScore(int $id, AIService $aiService): Response
{
    $user = $this->getUser();
    $event = $this->eventRepository->find($id);
    
    if (!$event) {
        return $this->json(['error' => 'Event not found'], 404);
    }
    
    // Vérifier si l'utilisateur a des compétences
    if (!$user->getSkills()) {
        return $this->json(['error' => 'No skills found for user'], 400);
    }
    
    $result = $aiService->calculateEventMatching(
        $user->getSkillsString(),
        $event->getDescription()
    );
    
    return $this->json([
        'score' => $result['score'],
        'recommendation' => $result['recommendation']
    ]);
}


}
