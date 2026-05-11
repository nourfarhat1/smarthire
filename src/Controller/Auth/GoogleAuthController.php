<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\GoogleOAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Route('/auth/google')]
class GoogleAuthController extends AbstractController
{
    public function __construct(
        private GoogleOAuthService $googleOAuthService,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private SluggerInterface $slugger,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Redirect to Google OAuth
     */
    #[Route('/login', name: 'app_google_login')]
    public function login(): Response
    {
        $authorizationUrl = $this->googleOAuthService->getAuthorizationUrl();
        return $this->redirect($authorizationUrl);
    }

    /**
     * Handle Google OAuth callback
     */
    #[Route('/callback', name: 'app_google_callback')]
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        $error = $request->query->get('error');

        if ($error) {
            $this->addFlash('error', 'Google authentication failed: ' . $error);
            return $this->redirectToRoute('app_login');
        }

        if (!$code) {
            $this->addFlash('error', 'No authorization code received from Google.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Exchange authorization code for access token
            $tokenData = $this->googleOAuthService->exchangeCodeForToken($code);
            
            // Get user information from Google
            $userInfo = $this->googleOAuthService->getUserInfo($tokenData['access_token']);

            // Check if user already exists
            $existingUser = $this->userRepository->findOneByEmail($userInfo['email']);

            if ($existingUser) {
                // User exists, log them in
                if ($existingUser->isBanned()) {
                    $this->addFlash('error', 'Your account has been banned. Please contact support.');
                    return $this->redirectToRoute('app_login');
                }

                // Update Google info if not set
                if (!$existingUser->getGoogleId()) {
                    $existingUser->setGoogleId($userInfo['id']);
                    $existingUser->setGoogleAccessToken($tokenData['access_token']);
                    if (isset($tokenData['refresh_token'])) {
                        $existingUser->setGoogleRefreshToken($tokenData['refresh_token']);
                    }
                }

                // Sync Google profile picture if available
                if (isset($userInfo['picture']) && $userInfo['picture']) {
                    $existingUser->setProfilePicture($userInfo['picture']);
                }

                // Update access token
                $existingUser->setGoogleAccessToken($tokenData['access_token']);
                if (isset($tokenData['refresh_token'])) {
                    $existingUser->setGoogleRefreshToken($tokenData['refresh_token']);
                }
                
                $this->userRepository->save($existingUser, true);

                // Check if profile picture was updated
                $profilePictureUpdated = false;
                if (isset($userInfo['picture']) && $userInfo['picture'] && $existingUser->getProfilePicture() !== $userInfo['picture']) {
                    $profilePictureUpdated = true;
                }

                // Authenticate the user
                $this->authenticateUser($existingUser, $request);
                
                // Add notification if profile picture was updated
                if ($profilePictureUpdated) {
                    $this->addFlash('info', 'Your Google profile picture has been synced.');
                }
                
                // Redirect to appropriate dashboard based on role
                return $this->redirectToRoute($this->getDashboardRoute($existingUser));
            } else {
                // New user, create account immediately with candidate role
                $user = new User();
                $user->setEmail($userInfo['email']);
                $user->setFirstName($userInfo['given_name'] ?? 'Google');
                $user->setLastName($userInfo['family_name'] ?? 'User');
                $user->setGoogleId($userInfo['id']);
                $user->setGoogleAccessToken($tokenData['access_token']);
                if (isset($tokenData['refresh_token'])) {
                    $user->setGoogleRefreshToken($tokenData['refresh_token']);
                }
                
                // Set Google profile picture if available
                if (isset($userInfo['picture']) && $userInfo['picture']) {
                    $user->setProfilePicture($userInfo['picture']);
                }
                
                // Generate a random password for Google users
                $randomPassword = bin2hex(random_bytes(16));
                $user->setPassword($randomPassword);
                
                // Set default values
                $user->setCreatedAt(new \DateTime());
                $user->setVerified(true); // Google users are pre-verified
                $user->setBanned(false);
                $user->setRoleId(1); // Candidate role
                
                // Save user
                $this->userRepository->save($user, true);

                // Authenticate the new user
                $this->authenticateUser($user, $request);
                
                $message = 'Account created successfully! Welcome to SmartHire!';
                if (isset($userInfo['picture']) && $userInfo['picture']) {
                    $message .= ' Your Google profile picture has been imported.';
                }
                $this->addFlash('success', $message);
                return $this->redirectToRoute($this->getDashboardRoute($user)); // Redirect to appropriate dashboard
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Google authentication failed: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * Get appropriate dashboard route based on user role
     */
    private function getDashboardRoute(User $user): string
    {
        return match ($user->getRoleId()) {
            1 => 'app_candidate_profile',     // Candidate
            2 => 'app_hr_dashboard',          // HR
            3 => 'app_admin',                 // Admin
            default => 'app_home',             // Fallback
        };
    }

    /**
     * Authenticate user programmatically
     */
    private function authenticateUser(User $user, Request $request): void
    {
        // Create authentication token
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        
        // Set token in security context
        $this->container->get('security.token_storage')->setToken($token);
        
        // Fire login event
        $event = new InteractiveLoginEvent($request, $token);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Link Google account to existing user
     */
    #[Route('/link', name: 'app_google_link')]
    public function link(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        if ($user->getGoogleId()) {
            $this->addFlash('info', 'Your account is already linked to Google.');
            return $this->redirectToRoute('app_profile');
        }

        $authorizationUrl = $this->googleOAuthService->getAuthorizationUrl();
        return $this->redirect($authorizationUrl);
    }

    /**
     * Unlink Google account
     */
    #[Route('/unlink', name: 'app_google_unlink', methods: ['POST'])]
    public function unlink(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        if (!$user->getGoogleId()) {
            $this->addFlash('error', 'Your account is not linked to Google.');
            return $this->redirectToRoute('app_profile');
        }

        $user->setGoogleId(null);
        $user->setGoogleAccessToken(null);
        $user->setGoogleRefreshToken(null);
        $this->userRepository->save($user, true);

        $this->addFlash('success', 'Google account unlinked successfully.');
        return $this->redirectToRoute('app_profile');
    }
}
