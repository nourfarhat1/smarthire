<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;
    private string $senderEmail;
    private string $senderName;

    public function __construct(MailerInterface $mailer, string $senderEmail = 'redcirclemxxn2001@gmail.com', string $senderName = 'SmartHire')
    {
        $this->mailer = $mailer;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    /**
     * Sends an OTP code via email to the given address.
     */
    public function sendOtp(string $toEmail, string $otpCode): void
    {
        $email = (new Email())
            ->from($this->senderEmail, $this->senderName)
            ->to($toEmail)
            ->subject('SmartHire — Your Password Reset Code')
            ->text(
                "Hello,\n\n" .
                "You requested a password reset for your SmartHire account.\n\n" .
                "Your verification code is:\n\n" .
                "        " . $otpCode . "\n\n" .
                "This code is valid for 10 minutes.\n" .
                "If you did not request this, please ignore this email.\n\n" .
                "— The SmartHire Team"
            );

        $this->mailer->send($email);
    }
}
