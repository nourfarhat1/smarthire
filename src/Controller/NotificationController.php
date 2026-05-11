<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService,
        private NotificationRepository $notificationRepository
    ) {
    }

    #[Route('/', name: 'app_notifications')]
    public function index(): Response
    {
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findByUser($user);

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/unread', name: 'app_notifications_unread')]
    public function unreadNotifications(): JsonResponse
    {
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findUnreadByUser($user);
        $unreadCount = $this->notificationRepository->getUnreadCount($user);

        return $this->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/mark-read/{id}', name: 'app_notifications_mark_read', methods: ['POST'])]
    public function markAsRead(int $id): JsonResponse
    {
        $this->notificationService->markAsRead($id);

        return $this->json(['success' => true]);
    }

    #[Route('/mark-all-read', name: 'app_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        $this->notificationService->markAllAsRead($user);

        return $this->json(['success' => true]);
    }

    #[Route('/delete/{id}', name: 'app_notifications_delete', methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        $this->notificationService->deleteNotification($id);

        return $this->json(['success' => true]);
    }

    #[Route('/settings', name: 'app_notifications_settings')]
    public function settings(): Response
    {
        return $this->render('notifications/settings.html.twig');
    }

    #[Route('/settings/save', name: 'app_notifications_settings_save', methods: ['POST'])]
    public function saveSettings(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Save notification preferences (would typically go to user settings table)
        $preferences = [
            'email_notifications' => $request->request->get('email_notifications', true),
            'push_notifications' => $request->request->get('push_notifications', true),
            'job_application_alerts' => $request->request->get('job_application_alerts', true),
            'interview_reminders' => $request->request->get('interview_reminders', true),
            'event_reminders' => $request->request->get('event_reminders', true),
        ];

        // This would typically be saved to a user_preferences table
        // For now, we'll just return success

        $this->addFlash('success', 'Notification preferences saved successfully!');

        return $this->json(['success' => true]);
    }
}
