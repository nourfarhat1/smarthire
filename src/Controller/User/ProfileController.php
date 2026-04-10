<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use App\Repository\JobRequestRepository;
use App\Repository\QuizResultRepository;
use App\Repository\AppEventRepository;
use App\Repository\InterviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private JobRequestRepository $jobRequestRepository,
        private QuizResultRepository $quizResultRepository,
        private UserRepository $userRepository,
        private AppEventRepository $appEventRepository,
        private InterviewRepository $interviewRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check user role and render appropriate profile
        $userRole = $user->getRoles();
        
        if (in_array('ROLE_HR', $userRole)) {
            // HR Profile - get HR-specific statistics
            $jobOffers = $this->userRepository->findBy(['id' => $user->getId()]);
            
            // Get Interview entities using the injected repository
            $interviews = $this->interviewRepository->findAll();
            
            $events = $this->appEventRepository->findBy(['organizer' => $user]);
            
            $stats = [
                'total_job_offers' => count($jobOffers),
                'active_job_offers' => count($jobOffers), // Simplified
                'total_interviews' => count($interviews),
                'pending_interviews' => count(array_filter($interviews, fn($interview) => method_exists($interview, 'getStatus') && $interview->getStatus() === 'PENDING')),
                'total_events' => count($events),
                'upcoming_events' => count(array_filter($events, fn($event) => $event->getEventDate() > new \DateTime())),
            ];
            
            return $this->render('profile/hr_index.html.twig', [
                'user' => $user,
                'stats' => $stats,
                'jobOffers' => $jobOffers,
                'interviews' => $interviews,
                'events' => $events,
            ]);
        } elseif (in_array('ROLE_ADMIN', $userRole)) {
            // Admin Profile - get system-wide statistics
            $allUsers = $this->userRepository->findAll();
            $allJobOffers = $this->entityManager->getRepository(\App\Entity\JobOffer::class)->findAll();
            $allApplications = $this->jobRequestRepository->findAll();
            $allEvents = $this->appEventRepository->findAll();
            $allQuizzes = $this->entityManager->getRepository(\App\Entity\Quiz::class)->findAll();
            
            $stats = [
                'total_users' => count($allUsers),
                'total_candidates' => count(array_filter($allUsers, fn($u) => in_array('ROLE_USER', $u->getRoles()))),
                'total_hr_users' => count(array_filter($allUsers, fn($u) => in_array('ROLE_HR', $u->getRoles()))),
                'total_job_offers' => count($allJobOffers),
                'total_applications' => count($allApplications),
                'pending_applications' => count(array_filter($allApplications, fn($app) => $app->getStatus() === 'PENDING')),
                'total_events' => count($allEvents),
                'total_quizzes' => count($allQuizzes),
                'active_quizzes' => count(array_filter($allQuizzes, fn($q) => method_exists($q, 'isActive') && $q->isActive())),
            ];
            
            return $this->render('profile/admin_index.html.twig', [
                'user' => $user,
                'stats' => $stats,
                'allUsers' => $allUsers,
                'allJobOffers' => $allJobOffers,
                'allApplications' => $allApplications,
                'allEvents' => $allEvents,
                'allQuizzes' => $allQuizzes,
            ]);
        }
        
        // Default: Candidate Profile
        $applications = $this->jobRequestRepository->findByCandidate($user->getId());
        $quizResults = $this->quizResultRepository->findByCandidate($user->getId());

        $totalApplications = count($applications);
        $pendingApplications = count(array_filter($applications, fn($app) => $app->getStatus() === 'PENDING'));
        $approvedApplications = count(array_filter($applications, fn($app) => $app->getStatus() === 'APPROVED'));
        $interviewCount = count(array_filter($applications, fn($app) => $app->getInterviews()->count() > 0));

        $totalQuizzes = count($quizResults);
        $passedQuizzes = count(array_filter($quizResults, fn($result) => $result->isPassed()));
        $averageScore = $totalQuizzes > 0 ? array_sum(array_map(fn($result) => $result->getScore(), $quizResults)) / $totalQuizzes : 0;

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'stats' => [
                'total_applications' => $totalApplications,
                'pending_applications' => $pendingApplications,
                'approved_applications' => $approvedApplications,
                'interview_count' => $interviewCount,
                'total_quizzes' => $totalQuizzes,
                'passed_quizzes' => $passedQuizzes,
                'average_score' => round($averageScore, 1),
            ],
            'recent_applications' => array_slice($applications, 0, 5),
            'recent_quiz_results' => array_slice($quizResults, 0, 5),
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check user role and render appropriate template
        $userRole = $user->getRoles();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password change
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($plainPassword);
            }

            // Handle profile picture upload
            $profilePicture = $form->get('profilePicture')->getData();
            if ($profilePicture) {
                $originalFilename = pathinfo($profilePicture->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $profilePicture->guessExtension();
                $newFilename = $originalFilename . '-' . uniqid() . '.' . $extension;
                
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/';
                $profilePicture->move($uploadsDir, $newFilename);
                
                // Update user profile
                $user->setProfilePicture($newFilename);
            }

            $this->userRepository->save($user, true);
            $this->addFlash('success', 'Your profile has been updated successfully!');

            return $this->redirectToRoute('app_profile');
        }

        // Render appropriate template based on role
        if (in_array('ROLE_HR', $userRole)) {
            return $this->render('profile/hr_edit.html.twig', [
                'form' => $form,
                'user' => $user,
            ]);
        } elseif (in_array('ROLE_ADMIN', $userRole)) {
            return $this->render('profile/admin_edit.html.twig', [
                'form' => $form,
                'user' => $user,
            ]);
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check user role for template rendering
        $userRole = $user->getRoles();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword');
            $newPassword = $form->get('newPassword');
            $confirmPassword = $form->get('confirmPassword');

            // Verify current password
            if ($user->getPassword() !== $currentPassword) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_profile_change_password');
            }

            // Verify new passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New passwords do not match.');
                return $this->redirectToRoute('app_profile_change_password');
            }

            // Set new password as plain text
            $user->setPassword($newPassword);

            $this->userRepository->save($user, true);
            $this->addFlash('success', 'Your password has been changed successfully!');

            return $this->redirectToRoute('app_profile');
        }

        // Render appropriate template based on role
        if (in_array('ROLE_HR', $userRole)) {
            return $this->render('profile/hr_change_password.html.twig', [
                'form' => $form,
            ]);
        } elseif (in_array('ROLE_ADMIN', $userRole)) {
            return $this->render('profile/admin_change_password.html.twig', [
                'form' => $form,
            ]);
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/upload-picture', name: 'app_profile_upload_picture', methods: ['POST'])]
    public function uploadPicture(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->files->has('profile_picture')) {
            $file = $request->files->get('profile_picture');
            
            // Validate file
            if ($file->isValid() && in_array($file->getClientMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
                $filename = uniqid() . '.' . $file->guessExtension();
                
                // Move file to uploads directory
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/';
                $file->move($uploadsDir, $filename);
                
                // Update user profile
                $user->setProfilePicture($filename);
                $this->userRepository->save($user, true);
                
                $this->addFlash('success', 'Profile picture updated successfully!');
            } else {
                $this->addFlash('error', 'Invalid file format. Please upload JPEG, PNG, or GIF.');
            }
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/admin', name: 'app_admin_profile')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminProfile(): Response
    {
        return $this->index();
    }

    #[Route('/hr', name: 'app_hr_profile')]
    #[IsGranted('ROLE_HR')]
    public function hrProfile(): Response
    {
        return $this->index();
    }

    #[Route('/candidate', name: 'app_candidate_profile')]
    #[IsGranted('ROLE_CANDIDATE')]
    public function candidateProfile(): Response
    {
        return $this->index();
    }
}
