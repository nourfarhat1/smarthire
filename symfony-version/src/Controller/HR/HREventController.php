<?php

namespace App\Controller\HR;

use App\Entity\AppEvent;
use App\Repository\AppEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\WeatherService;
#[Route('/hr/events')]
#[IsGranted('ROLE_HR')]
class HREventController extends AbstractController
{
    public function __construct(
        private AppEventRepository $appEventRepository
    ) {
    }

    #[Route('/', name: 'app_hr_events')]
    public function index(): Response
    {
        $hrId = $this->getUser()->getId();
        $events = $this->appEventRepository->findByOrganizer($hrId);

        return $this->render('hr/events/index.html.twig', [
            'events' => $events,
        ]);
    }
#[Route('/new', name: 'app_hr_events_new', methods: ['GET', 'POST'])]
public function new(Request $request): Response
{
    $event = new AppEvent();

    if ($request->isMethod('POST')) {
        $event->setName($request->request->get('name'));
        $event->setDescription($request->request->get('description'));
        
        // Gestion de la date
        $eventDateStr = $request->request->get('eventDate');
        if ($eventDateStr) {
            try {
                $event->setEventDate(new \DateTime($eventDateStr));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid date format.');
                return $this->redirectToRoute('app_hr_events_new');
            }
        }
        
        // Récupérer la région et l'adresse détaillée
        $region = $request->request->get('location');
        $addressDetail = $request->request->get('address_detail');
        
        // Combiner région + adresse détaillée
        $fullLocation = $region;
        if ($addressDetail) {
            $fullLocation = $region . ' - ' . $addressDetail;
        }
        
        $event->setLocation($fullLocation);
        $event->setMaxParticipants((int)$request->request->get('maxParticipants'));
        $event->setOrganizer($this->getUser());

        $this->appEventRepository->save($event, true);

        $this->addFlash('success', 'Event created successfully!');

        return $this->redirectToRoute('app_hr_events');
    }

    return $this->render('hr/events/new.html.twig', [
        'event' => $event,
    ]);
}
   #[Route('/{id}', name: 'app_hr_events_show', requirements: ['id' => '\d+'])]
public function show(AppEvent $event, WeatherService $weatherService): Response
{
    // Check if HR user is the organizer
    if ($event->getOrganizer()->getId() !== $this->getUser()->getId()) {
        throw $this->createAccessDeniedException('You can only view your own events.');
    }

    // Extraire la ville depuis la localisation
    $city = $this->extractCityFromLocation($event->getLocation());
    
    // Obtenir la météo
    $weather = $weatherService->getWeather($city);

    return $this->render('hr/events/show.html.twig', [
        'event' => $event,
        'weather' => $weather
    ]);
}

/**
 * Extrait le nom de la ville à partir de la localisation de l'événement
 */
private function extractCityFromLocation(string $location): string
{
    // Mapping des régions/gouvernorats tunisiens
    $cityMap = [
        'Tunis' => 'Tunis',
        'Ariana' => 'Ariana',
        'Ben Arous' => 'Ben Arous',
        'Manouba' => 'Manouba',
        'Nabeul' => 'Nabeul',
        'Zaghouan' => 'Zaghouan',
        'Bizerte' => 'Bizerte',
        'Béja' => 'Beja',
        'Jendouba' => 'Jendouba',
        'Le Kef' => 'El Kef',
        'Siliana' => 'Siliana',
        'Kairouan' => 'Kairouan',
        'Kasserine' => 'Kasserine',
        'Sidi Bouzid' => 'Sidi Bouzid',
        'Sousse' => 'Sousse',
        'Monastir' => 'Monastir',
        'Mahdia' => 'Mahdia',
        'Sfax' => 'Sfax',
        'Gabès' => 'Gabes',
        'Médenine' => 'Medenine',
        'Tataouine' => 'Tataouine',
        'Gafsa' => 'Gafsa',
        'Tozeur' => 'Tozeur',
        'Kebili' => 'Kebili',
    ];
    
    // Chercher la région dans la chaîne de localisation
    foreach ($cityMap as $key => $city) {
        if (strpos($location, $key) !== false) {
            return $city;
        }
    }
    
    // Valeur par défaut si aucune correspondance
    return 'Tunis';
}

 #[Route('/{id}/edit', name: 'app_hr_events_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, AppEvent $event): Response
{
    // Check if HR user is the organizer
    if ($event->getOrganizer()->getId() !== $this->getUser()->getId()) {
        throw $this->createAccessDeniedException('You can only edit your own events.');
    }

    if ($request->isMethod('POST')) {
        $event->setName($request->request->get('name'));
        $event->setDescription($request->request->get('description'));
        
        // 🔥 CORRECTION : Utilise 'eventDate' au lieu de 'event_date' 🔥
        $eventDate = $request->request->get('eventDate');
        if ($eventDate) {
            $event->setEventDate(new \DateTime($eventDate));
        }
        
        $event->setLocation($request->request->get('location'));
        $event->setMaxParticipants((int)$request->request->get('maxParticipants'));

        $this->appEventRepository->save($event, true);

        $this->addFlash('success', 'Event updated successfully!');

        return $this->redirectToRoute('app_hr_events');
    }

    return $this->render('hr/events/edit.html.twig', [
        'event' => $event,
    ]);
}

    #[Route('/{id}/manage-capacity', name: 'app_hr_events_capacity')]
    public function manageCapacity(Request $request, AppEvent $event): Response
    {
        // Check if HR user is the organizer
        if ($event->getOrganizer()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only manage your own events.');
        }

        if ($request->isMethod('POST')) {
            $newCapacity = (int)$request->request->get('max_participants');
            $event->setMaxParticipants($newCapacity);
            $this->appEventRepository->save($event, true);

            $this->addFlash('success', 'Event capacity updated successfully!');
            return $this->redirectToRoute('app_hr_events_capacity', ['id' => $event->getId()]);
        }

        $participants = $this->appEventRepository->getEventParticipants($event->getId());
        $currentCapacity = $event->getMaxParticipants();
        $registeredCount = count($participants);

        return $this->render('hr/events/capacity.html.twig', [
            'event' => $event,
            'participants' => $participants,
            'currentCapacity' => $currentCapacity,
            'registeredCount' => $registeredCount,
            'availableSpots' => $currentCapacity - $registeredCount,
        ]);
    }

    #[Route('/{id}/participants', name: 'app_hr_events_participants')]
    public function participants(AppEvent $event): Response
    {
        // Check if HR user is the organizer
        if ($event->getOrganizer()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only view participants for your own events.');
        }

        $participants = $this->appEventRepository->getEventParticipants($event->getId());

        return $this->render('hr/events/participants.html.twig', [
            'event' => $event,
            'participants' => $participants,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_hr_events_delete', methods: ['POST'])]
    public function delete(AppEvent $event): Response
    {
        // Check if HR user is the organizer
        if ($event->getOrganizer()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only delete your own events.');
        }

        $this->appEventRepository->remove($event, true);

        $this->addFlash('success', 'Event deleted successfully!');

        return $this->redirectToRoute('app_hr_events');
    }
}
