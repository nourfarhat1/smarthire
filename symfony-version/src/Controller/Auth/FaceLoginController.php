<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AIService;
use App\Service\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/face-login')]
class FaceLoginController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private AIService $aiService,
        private FileUploadService $fileUploadService,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/', name: 'app_face_login')]
    public function index(): Response
    {
        return $this->render('auth/face_login.html.twig');
    }

    #[Route('/capture', name: 'app_face_login_capture', methods: ['POST'])]
    public function captureFace(Request $request): JsonResponse
    {
        try {
            $imageData = $request->request->get('image');
            $email = $request->request->get('email');
            
            if (empty($imageData) || empty($email)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Missing image data or email'
                ], 400);
            }

            // Find user by email
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            // Check if user has a profile picture
            if (!$user->getProfilePicture()) {
                return $this->json([
                    'success' => false,
                    'error' => 'No profile picture found for this user'
                ], 400);
            }

            // Save captured image temporarily
            $capturedImagePath = $this->saveCapturedImage($imageData);
            $storedImagePath = $this->fileUploadService->getAbsolutePath($user->getProfilePicture());

            // Validate captured image
            $validation = $this->aiService->validateFaceImage($capturedImagePath);
            if (!$validation['is_valid']) {
                return $this->json([
                    'success' => false,
                    'error' => 'Face image quality too low',
                    'issues' => $validation['issues'],
                    'recommendations' => $validation['recommendations']
                ], 400);
            }

            // Compare faces
            $comparison = $this->aiService->compareFaces($capturedImagePath, $storedImagePath);

            // Clean up temporary image
            unlink($capturedImagePath);

            if ($comparison['is_match'] && $comparison['match_confidence'] > 75) {
                // Login successful - create session
                return $this->json([
                    'success' => true,
                    'message' => 'Face recognition successful',
                    'match_confidence' => $comparison['match_confidence'],
                    'analysis' => $comparison['analysis_description'],
                    'redirect_url' => $this->generateUrl('app_dashboard')
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => 'Face recognition failed',
                    'match_confidence' => $comparison['match_confidence'],
                    'analysis' => $comparison['analysis_description'],
                    'key_differences' => $comparison['key_differences'] ?? []
                ], 401);
            }

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Face login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/verify', name: 'app_face_login_verify', methods: ['POST'])]
    public function verifyFaceLogin(Request $request): JsonResponse
    {
        try {
            $email = $request->request->get('email');
            $imageData = $request->request->get('image');
            
            if (empty($email) || empty($imageData)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Missing required data'
                ], 400);
            }

            // Find user
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            // Check user status
            if (!$user->isVerified()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Account not verified'
                ], 403);
            }

            if ($user->isBanned()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Account is banned'
                ], 403);
            }

            // Perform face comparison
            $capturedImagePath = $this->saveCapturedImage($imageData);
            $storedImagePath = $this->fileUploadService->getAbsolutePath($user->getProfilePicture());

            $comparison = $this->aiService->compareFaces($capturedImagePath, $storedImagePath);

            // Clean up
            unlink($capturedImagePath);

            if ($comparison['is_match'] && $comparison['match_confidence'] > 75) {
                // Log the user in
                // In a real implementation, you would use Symfony's authentication system
                // For now, we'll return success with user info
                
                return $this->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'firstName' => $user->getFirstName(),
                        'lastName' => $user->getLastName(),
                        'role' => $user->getRoleName()
                    ],
                    'match_confidence' => $comparison['match_confidence'],
                    'redirect_url' => $this->getRedirectUrlForUser($user)
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => 'Face does not match stored profile',
                    'match_confidence' => $comparison['match_confidence'],
                    'analysis' => $comparison['analysis_description']
                ], 401);
            }

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/setup/{id}', name: 'app_face_login_setup')]
    public function setupFaceLogin(User $user): Response
    {
        // Check if current user can setup face login for this user
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You cannot setup face login for this user');
        }

        return $this->render('auth/face_login_setup.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/setup/{id}/register', name: 'app_face_login_setup_register', methods: ['POST'])]
    public function registerFace(User $user, Request $request): JsonResponse
    {
        try {
            $imageData = $request->request->get('image');
            
            if (empty($imageData)) {
                return $this->json([
                    'success' => false,
                    'error' => 'No face image provided'
                ], 400);
            }

            // Save and validate face image
            $faceImagePath = $this->saveCapturedImage($imageData);
            $validation = $this->aiService->validateFaceImage($faceImagePath);

            if (!$validation['is_valid']) {
                unlink($faceImagePath);
                return $this->json([
                    'success' => false,
                    'error' => 'Face image quality insufficient',
                    'issues' => $validation['issues'],
                    'recommendations' => $validation['recommendations']
                ], 400);
            }

            // Extract face features
            $features = $this->aiService->extractFaceFeatures($faceImagePath);
            
            if (!$features['face_detected']) {
                unlink($faceImagePath);
                return $this->json([
                    'success' => false,
                    'error' => 'No face detected in image'
                ], 400);
            }

            // Save as profile picture
            $filename = $this->fileUploadService->uploadProfilePicture($faceImagePath, $user);
            unlink($faceImagePath);

            // Update user
            $user->setProfilePicture($filename);
            $user->setFaceLoginEnabled(true);
            $this->userRepository->save($user, true);

            return $this->json([
                'success' => true,
                'message' => 'Face login setup completed successfully',
                'features' => $features,
                'validation' => $validation
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Face setup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function saveCapturedImage(string $imageData): string
    {
        // Remove data URL prefix if present
        if (strpos($imageData, 'data:image') === 0) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        }

        $imageData = base64_decode($imageData);
        if ($imageData === false) {
            throw new \Exception('Invalid image data');
        }

        $filename = 'face_capture_' . uniqid() . '.jpg';
        $filepath = sys_get_temp_dir() . '/' . $filename;

        if (file_put_contents($filepath, $imageData) === false) {
            throw new \Exception('Failed to save captured image');
        }

        return $filepath;
    }

    private function getRedirectUrlForUser(User $user): string
    {
        return match($user->getRoleName()) {
            'ROLE_ADMIN' => $this->generateUrl('app_admin_dashboard'),
            'ROLE_HR' => $this->generateUrl('app_hr_dashboard'),
            'ROLE_USER' => $this->generateUrl('app_candidate_dashboard'),
            default => $this->generateUrl('app_dashboard')
        };
    }
}
