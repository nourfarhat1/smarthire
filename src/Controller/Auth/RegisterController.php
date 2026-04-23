<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

#[Route('/register')]
class RegisterController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    #[Route('/', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            // Check if email already exists
            if ($this->userRepository->findOneByEmail($user->getEmail())) {
                $this->addFlash('error', 'This email is already registered.');
                return $this->redirectToRoute('app_register');
            }

            // Store password as plain text
            $user->setPassword($user->getPassword());

            // Set default values
            $user->setCreatedAt(new \DateTime());
            $user->setVerified(false);
            $user->setBanned(false);
            
            // Set default roleId to 1 (Candidate) if not set
            if (!$user->getRoleId()) {
                $user->setRoleId(1);
            }

            // Save user
            $this->userRepository->save($user, true);

            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            $user->setVerificationToken($verificationToken);
            $this->userRepository->save($user, true);

            // Send verification email (would implement EmailService here)
            $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');

            // Log the user in automatically
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/verify/{token}', name: 'app_register_verify')]
    public function verifyEmail(string $token): Response
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Invalid verification token.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your account is already verified.');
            return $this->redirectToRoute('app_login');
        }

        // Mark user as verified
        $user->setVerified(true);
        $user->setVerificationToken(null);
        $this->userRepository->save($user, true);

        $this->addFlash('success', 'Your account has been verified successfully! You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification', name: 'app_register_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): Response
    {
        $email = $request->request->get('email');

        if (!$email) {
            $this->addFlash('error', 'Please provide your email address.');
            return $this->redirectToRoute('app_register');
        }

        $user = $this->userRepository->findOneByEmail($email);

        if (!$user) {
            $this->addFlash('error', 'No account found with this email address.');
            return $this->redirectToRoute('app_register');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your account is already verified.');
            return $this->redirectToRoute('app_register');
        }

        // Generate new verification token
        $verificationToken = bin2hex(random_bytes(32));
        $user->setVerificationToken($verificationToken);
        $this->userRepository->save($user, true);

        // Send verification email (would implement EmailService here)
        $this->addFlash('success', 'Verification email has been sent to your email address.');

        return $this->redirectToRoute('app_register');
    }
}
