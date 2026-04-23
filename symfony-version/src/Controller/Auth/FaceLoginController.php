<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/face-login')]
class FaceLoginController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    /**
     * Face login page — shown to unauthenticated users
     */
    #[Route('/', name: 'app_face_login')]
    public function index(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('auth/face_login.html.twig');
    }

    /**
     * Called after Faceio recognizes a face on the LOGIN page.
     * Receives the facialId from the frontend, finds the matching user,
     * logs them into Symfony, and returns a redirect URL.
     */
    #[Route('/authenticate', name: 'app_face_login_authenticate', methods: ['POST'])]
    public function authenticate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $facialId = $data['facialId'] ?? null;

            if (empty($facialId)) {
                return $this->json([
                    'success' => false,
                    'error' => 'No facial ID received.'
                ], 400);
            }

            // Find user by their stored faceioId
            $user = $this->userRepository->findOneBy(['faceioId' => $facialId]);

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'error' => 'No account linked to this face. Please enroll first.'
                ], 404);
            }

            if ($user->isBanned()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Your account has been suspended.'
                ], 403);
            }

            if (!$user->isVerified()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Your account is not verified yet.'
                ], 403);
            }

            // Log the user into Symfony's security system
            $this->security->login($user, 'App\Security\PlainTextAuthenticator', 'main');

            // Determine redirect based on role
            $redirectUrl = $this->getRedirectUrlForUser($user);

            return $this->json([
                'success' => true,
                'message' => 'Welcome, ' . $user->getFirstName() . '!',
                'redirect_url' => $redirectUrl
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Authentication failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Face enrollment page — only for logged-in users who want to enable face login.
     * Accessible from the profile page.
     */
    #[Route('/enroll', name: 'app_face_login_enroll')]
    #[IsGranted('ROLE_USER')]
    public function enroll(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('auth/face_login_setup.html.twig', [
            'user' => $user,
            'already_enrolled' => $user->getFaceioId() !== null
        ]);
    }

    /**
     * Called after Faceio enrolls a face on the SETUP page.
     * Receives the facialId from the frontend and saves it on the user.
     */
    #[Route('/enroll/save', name: 'app_face_login_enroll_save', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function saveEnrollment(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $data = json_decode($request->getContent(), true);
            $facialId = $data['facialId'] ?? null;

            if (empty($facialId)) {
                return $this->json([
                    'success' => false,
                    'error' => 'No facial ID received from Faceio.'
                ], 400);
            }

            // Check no other user already has this facialId
            $existing = $this->userRepository->findOneBy(['faceioId' => $facialId]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json([
                    'success' => false,
                    'error' => 'This face is already linked to another account.'
                ], 409);
            }

            // Save the facialId and enable face login
            $user->setFaceioId($facialId);
            $user->setFaceLoginEnabled(true);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Face login enabled successfully!'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Enrollment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disable face login for the current user
     */
    #[Route('/disable', name: 'app_face_login_disable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function disable(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $user->setFaceioId(null);
        $user->setFaceLoginEnabled(false);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Face login has been disabled.'
        ]);
    }

    private function getRedirectUrlForUser(User $user): string
    {
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->generateUrl('app_admin');
        }
        if (in_array('ROLE_HR', $user->getRoles())) {
            return $this->generateUrl('app_hr_dashboard');
        }
        if (in_array('ROLE_CANDIDATE', $user->getRoles())) {
            return $this->generateUrl('app_candidate_dashboard');
        }
        return $this->generateUrl('app_profile');
    }
}
