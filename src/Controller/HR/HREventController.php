<?php

namespace App\Controller\HR;

use App\Entity\AppEvent;
use App\Repository\AppEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
            $event->setEventDate(new \DateTime($request->request->get('event_date')));
            $event->setLocation($request->request->get('location'));
            $event->setMaxParticipants((int)$request->request->get('max_participants'));
            $event->setOrganizer($this->getUser());

            $this->appEventRepository->save($event, true);

            $this->addFlash('success', 'Event created successfully!');

            return $this->redirectToRoute('app_hr_events');
        }

        return $this->render('hr/events/new.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}', name: 'app_hr_events_show')]
    public function show(AppEvent $event): Response
    {
        // Check if HR user is the organizer
        if ($event->getOrganizer()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only view your own events.');
        }

        return $this->render('hr/events/show.html.twig', [
            'event' => $event,
        ]);
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
            $event->setEventDate(new \DateTime($request->request->get('event_date')));
            $event->setLocation($request->request->get('location'));
            $event->setMaxParticipants((int)$request->request->get('max_participants'));

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
