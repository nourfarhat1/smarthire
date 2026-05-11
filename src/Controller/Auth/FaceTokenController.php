<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile')]
class FaceTokenController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    /**
     * Disable face login for current user
     */
    #[Route('/disable-face-login', name: 'app_profile_disable_face_login', methods: ['POST'])]
    public function disableFaceLogin(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        try {
            $user->setFaceLoginEnabled(false);
            $user->setFaceTokens([]);
            $user->setFaceFeatures(null);
            $this->userRepository->save($user, true);

            return new JsonResponse(['success' => true, 'message' => 'Face login disabled successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error disabling face login'], 500);
        }
    }

    /**
     * Get face tokens for current user
     */
    #[Route('/get-face-tokens', name: 'app_profile_get_face_tokens', methods: ['GET'])]
    public function getFaceTokens(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        try {
            $tokens = [];
            $faceTokens = $user->getFaceTokens();
            
            if (!empty($faceTokens)) {
                foreach ($faceTokens as $token) {
                    $tokens[] = [
                        'token' => $token,
                        'createdAt' => time() // FaceIO doesn't provide timestamps, so we use current time
                    ];
                }
            }

            return new JsonResponse(['success' => true, 'tokens' => $tokens]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error retrieving face tokens'], 500);
        }
    }

    /**
     * Delete a specific face token
     */
    #[Route('/delete-face-token', name: 'app_profile_delete_face_token', methods: ['POST'])]
    public function deleteFaceToken(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $tokenToDelete = $data['token'] ?? null;

        if (!$tokenToDelete) {
            return new JsonResponse(['success' => false, 'message' => 'Token not provided'], 400);
        }

        try {
            $currentTokens = $user->getFaceTokens();
            
            // Remove the specific token
            $updatedTokens = array_filter($currentTokens, function($token) use ($tokenToDelete) {
                return $token !== $tokenToDelete;
            });

            $user->setFaceTokens(array_values($updatedTokens));
            
            // If no tokens left, disable face login
            if (empty($updatedTokens)) {
                $user->setFaceLoginEnabled(false);
                $user->setFaceFeatures(null);
            }

            $this->userRepository->save($user, true);

            return new JsonResponse(['success' => true, 'message' => 'Face token deleted successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error deleting face token'], 500);
        }
    }

    /**
     * Check for duplicate faces during enrollment
     */
    #[Route('/check-face-duplicate', name: 'app_profile_check_face_duplicate', methods: ['POST'])]
    public function checkFaceDuplicate(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $facialId = $data['facialId'] ?? null;

        if (!$facialId) {
            return new JsonResponse(['success' => false, 'message' => 'Facial ID not provided'], 400);
        }

        try {
            // Check if this facial ID exists for any other user
            $existingUser = $this->userRepository->findOneBy(['faceFeatures' => $facialId]);

            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'This face is already registered to another account',
                    'duplicateUser' => [
                        'email' => $existingUser->getEmail(),
                        'firstName' => $existingUser->getFirstName(),
                        'lastName' => $existingUser->getLastName()
                    ]
                ]);
            }

            return new JsonResponse(['success' => true, 'message' => 'No duplicate face found']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error checking for duplicate faces'], 500);
        }
    }
}
