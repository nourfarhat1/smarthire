<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use App\Service\SMSService;
use App\Service\EmailService;
use App\Form\ResetPasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/forgot-password')]
class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private SMSService $smsService,
        private EmailService $emailService
    ) {
    }

    /**
     * Step 1 — User enters their email to request an OTP
     */
    #[Route('/', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function requestOtp(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));

            if (empty($email)) {
                $this->addFlash('error', 'Please enter your email address.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user = $this->userRepository->findOneByEmail($email);

            if (!$user) {
                // Don't reveal whether email exists
                $this->addFlash('success', 'If this email is registered, you will receive a reset code shortly.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Generate 6-digit OTP
            $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpiry = new \DateTime('+10 minutes');

            $user->setOtpCode($otpCode);
            $user->setOtpExpiry($otpExpiry);
            $this->userRepository->save($user, true);

            $smsSent = false;
            $emailSent = false;

            // Send via SMS if user has a phone number
            if ($user->getPhoneNumber()) {
                try {
                    $phone = $user->getPhoneNumber();
                    // Add Tunisia country code if not already international
                    if (!str_starts_with($phone, '+')) {
                        $phone = '+216' . $phone;
                    }
                    $this->smsService->sendOtp($phone, $otpCode);
                    $smsSent = true;
                } catch (\Exception $e) {
                    // SMS failed — continue to try email
                }
            }

            // Send via email
            try {
                $this->emailService->sendOtp($user->getEmail(), $otpCode);
                $emailSent = true;
            } catch (\Exception $e) {
                // Email failed
            }

            if (!$smsSent && !$emailSent) {
                $this->addFlash('error', 'Failed to send reset code. Please try again later.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Store email in session to use in next steps
            $request->getSession()->set('reset_email', $email);

            $channels = [];
            if ($emailSent) $channels[] = 'email';
            if ($smsSent) $channels[] = 'SMS';

            $this->addFlash('success', 'A 6-digit reset code has been sent to your ' . implode(' and ', $channels) . '.');

            return $this->redirectToRoute('app_forgot_password_verify');
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    /**
     * Step 2 — User enters the 6-digit OTP
     */
    #[Route('/verify', name: 'app_forgot_password_verify', methods: ['GET', 'POST'])]
    public function verifyOtp(Request $request): Response
    {
        $email = $request->getSession()->get('reset_email');

        if (!$email) {
            $this->addFlash('error', 'Session expired. Please start again.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $enteredOtp = trim($request->request->get('otp_code', ''));

            $user = $this->userRepository->findOneByEmail($email);

            if (!$user) {
                $this->addFlash('error', 'Something went wrong. Please try again.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Check OTP expiry
            if (!$user->getOtpExpiry() || $user->getOtpExpiry() < new \DateTime()) {
                $this->addFlash('error', 'Your code has expired. Please request a new one.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Check OTP match
            if ($user->getOtpCode() !== $enteredOtp) {
                $this->addFlash('error', 'Invalid code. Please check and try again.');
                return $this->redirectToRoute('app_forgot_password_verify');
            }

            // OTP is valid — clear it and allow password reset
            $user->setOtpCode(null);
            $user->setOtpExpiry(null);
            $this->userRepository->save($user, true);

            // Mark session as OTP verified
            $request->getSession()->set('otp_verified', true);

            return $this->redirectToRoute('app_forgot_password_reset');
        }

        return $this->render('auth/verify_otp.html.twig', [
            'email' => $email
        ]);
    }

    /**
     * Step 3 — User sets a new password
     */
    #[Route('/reset', name: 'app_forgot_password_reset', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request): Response
    {
        $email = $request->getSession()->get('reset_email');
        $otpVerified = $request->getSession()->get('otp_verified');

        if (!$email || !$otpVerified) {
            $this->addFlash('error', 'Please verify your identity first.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $user = $this->userRepository->findOneByEmail($email);

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            if (empty($newPassword)) {
                $this->addFlash('error', 'Please enter a new password.');
                return $this->redirectToRoute('app_forgot_password_reset');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->redirectToRoute('app_forgot_password_reset');
            }

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters.');
                return $this->redirectToRoute('app_forgot_password_reset');
            }

            // Save new plain text password
            $user->setPassword($newPassword);
            $this->userRepository->save($user, true);

            // Clear session
            $request->getSession()->remove('reset_email');
            $request->getSession()->remove('otp_verified');

            $this->addFlash('success', 'Your password has been reset successfully! You can now log in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset_password.html.twig');
    }

    /**
     * Resend OTP
     */
    #[Route('/resend', name: 'app_forgot_password_resend', methods: ['POST'])]
    public function resendOtp(Request $request): Response
    {
        $email = $request->getSession()->get('reset_email');

        if (!$email) {
            $this->addFlash('error', 'Session expired. Please start again.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $user = $this->userRepository->findOneByEmail($email);

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // Generate new OTP
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setOtpCode($otpCode);
        $user->setOtpExpiry(new \DateTime('+10 minutes'));
        $this->userRepository->save($user, true);

        $sent = false;

        if ($user->getPhoneNumber()) {
            try {
                $phone = $user->getPhoneNumber();
                if (!str_starts_with($phone, '+')) {
                    $phone = '+216' . $phone;
                }
                $this->smsService->sendOtp($phone, $otpCode);
                $sent = true;
            } catch (\Exception $e) {}
        }

        try {
            $this->emailService->sendOtp($user->getEmail(), $otpCode);
            $sent = true;
        } catch (\Exception $e) {}

        if ($sent) {
            $this->addFlash('success', 'A new code has been sent.');
        } else {
            $this->addFlash('error', 'Failed to resend code. Please try again.');
        }

        return $this->redirectToRoute('app_forgot_password_verify');
    }
}
