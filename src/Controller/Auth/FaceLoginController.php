<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\FacePlusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/face-login')]
class FaceLoginController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private FacePlusService $facePlusService,
        private SluggerInterface $slugger
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
     * Process face detection from uploaded image
     */
    #[Route('/detect', name: 'app_face_login_detect', methods: ['POST'])]
    public function detectFace(Request $request): JsonResponse
    {
        try {
            $imageData = $request->get('image');
            
            if (empty($imageData)) {
                return $this->json([
                    'success' => false,
                    'error' => 'No image data received.'
                ], 400);
            }

            // Remove data URL prefix if present
            if (str_starts_with($imageData, 'data:image/')) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
            }

            $result = $this->facePlusService->detectFaces($imageData);

            if (!isset($result['faces']) || empty($result['faces'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'No face detected. Please ensure good lighting and position your face clearly in the camera.'
                ]);
            }

            $face = $result['faces'][0];
            $faceToken = $face['face_token'];

            return $this->json([
                'success' => true,
                'face_token' => $faceToken,
                'face_data' => [
                    'age' => $face['attributes']['age']['value'] ?? null,
                    'gender' => $face['attributes']['gender']['value'] ?? null,
                    'emotion' => $face['attributes']['emotion'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Face detection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Authenticate user using face recognition
     */
    #[Route('/authenticate', name: 'app_face_login_authenticate', methods: ['POST'])]
    public function authenticate(Request $request): JsonResponse
    {
        try {
            $faceToken = $request->get('face_token');
            
            if (empty($faceToken)) {
                return $this->json([
                    'success' => false,
                    'error' => 'No face token provided.'
                ], 400);
            }

            // Get all users with face login enabled
            $users = $this->userRepository->findBy(['faceLoginEnabled' => true]);
            
            if (empty($users)) {
                return $this->json([
                    'success' => false,
                    'error' => 'No users with face login enabled.'
                ], 404);
            }

            // Try to match the face with each user's stored face tokens
            foreach ($users as $user) {
                if (!$user->getFaceTokens() || empty($user->getFaceTokens())) {
                    continue;
                }

                foreach ($user->getFaceTokens() as $storedFaceToken) {
                    try {
                        $comparisonResult = $this->facePlusService->compareFaces($faceToken, $storedFaceToken);
                        
                        if (isset($comparisonResult['confidence']) && $comparisonResult['confidence'] > 50) {
                            // Face matches! Authenticate the user
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
                        }
                    } catch (\Exception $e) {
                        // Continue trying other faces
                        continue;
                    }
                }
            }

            return $this->json([
                'success' => false,
                'error' => 'Face not recognized. Please try again with better lighting or use traditional login.'
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
            'already_enrolled' => $user->isFaceLoginEnabled() && !empty($user->getFaceTokens())
        ]);
    }

    /**
     * Process face enrollment
     */
    #[Route('/enroll/process', name: 'app_face_login_enroll_process', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function processEnrollment(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $faceToken = $request->get('face_token');
            
            if (empty($faceToken)) {
                return $this->json([
                    'success' => false,
                    'error' => 'No face token provided.'
                ], 400);
            }

            // Get current face tokens or initialize empty array
            $faceTokens = $user->getFaceTokens() ?? [];
            
            // Add new face token if not already present
            if (!in_array($faceToken, $faceTokens)) {
                $faceTokens[] = $faceToken;
                $user->setFaceTokens($faceTokens);
            }

            // Enable face login
            $user->setFaceLoginEnabled(true);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Face enrolled successfully! You can now use face recognition to login.',
                'face_tokens_count' => count($faceTokens)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Enrollment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a specific face token
     */
    #[Route('/remove-face', name: 'app_face_login_remove_face', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function removeFace(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $faceToken = $request->get('face_token');
            
            if (empty($faceToken)) {
                return $this->json([
                    'success' => false,
                    'error' => 'No face token provided.'
                ], 400);
            }

            $faceTokens = $user->getFaceTokens() ?? [];
            
            // Remove the specific face token
            $faceTokens = array_filter($faceTokens, function($token) use ($faceToken) {
                return $token !== $faceToken;
            });

            $user->setFaceTokens(array_values($faceTokens));
            
            // Disable face login if no tokens left
            if (empty($faceTokens)) {
                $user->setFaceLoginEnabled(false);
            }
            
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Face removed successfully.',
                'face_tokens_count' => count($faceTokens),
                'face_login_enabled' => $user->isFaceLoginEnabled()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to remove face: ' . $e->getMessage()
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

        $user->setFaceTokens([]);
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
