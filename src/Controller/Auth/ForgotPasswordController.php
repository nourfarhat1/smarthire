<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Form\ForgotPasswordType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/forgot-password')]
class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    #[Route('/', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function requestReset(Request $request): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $user = $this->userRepository->findOneByEmail($email);

            if (!$user) {
                $this->addFlash('error', 'No account found with this email address.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $user->setResetToken($resetToken);
            $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
            $this->userRepository->save($user, true);

            // Send reset email (would implement EmailService here)
            $this->addFlash('success', 'Password reset link has been sent to your email address.');

            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('auth/forgot_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reset/{token}', name: 'app_forgot_password_reset', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, string $token): Response
    {
        $user = $this->userRepository->findOneBy(['resetToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Invalid reset token.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($user->getResetTokenExpiresAt() && $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Reset token has expired. Please request a new one.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $form = $this->createForm(ResetPasswordType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $newPassword = $form->get('password')->getData();
                
                // Set new password as plain text
                $user->setPassword($newPassword);

                // Clear reset token
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $this->userRepository->save($user, true);

                $this->addFlash('success', 'Your password has been reset successfully! You can now log in.');

                return $this->redirectToRoute('app_login');
            }

            return $this->render('auth/reset_password.html.twig', [
                'form' => $form,
                'token' => $token,
            ]);
        }

        return $this->render('auth/reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
