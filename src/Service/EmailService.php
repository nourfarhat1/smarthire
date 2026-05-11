<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EmailService
{
    private MailerInterface $mailer;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        ParameterBagInterface $parameterBag
    ) {
        $this->mailer = $mailer;
        $this->senderEmail = $parameterBag->get('app.gmail_address');
        $this->senderName = 'SmartHire';
    }

    /**
     * Sends an OTP code via email using Symfony Mailer and Gmail
     */
    public function sendOtp(string $toEmail, string $otpCode): void
    {
        $email = (new Email())
            ->from($this->senderEmail)
            ->to($toEmail)
            ->subject('SmartHire - Password Reset Code')
            ->html($this->getOTPEmailTemplate($otpCode));

        $this->mailer->send($email);
    }

    /**
     * Get HTML email template for OTP
     */
    private function getOTPEmailTemplate(string $otpCode): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Password Reset Code</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4f46e5; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9fafb; }
                .code { background: #e5e7eb; padding: 15px; margin: 20px 0; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 3px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
                .btn { display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔐 SmartHire Password Reset</h1>
                </div>
                <div class="content">
                    <h2>Password Reset Request</h2>
                    <p>You requested to reset your password for your SmartHire account. Use the verification code below to proceed:</p>
                    
                    <div class="code">' . htmlspecialchars($otpCode) . '</div>
                    
                    <p><strong>This code will expire in 15 minutes.</strong></p>
                    <p>If you didn\'t request this password reset, please ignore this email.</p>
                    
                    <p>For security reasons, please:</p>
                    <ul>
                        <li>Never share this code with anyone</li>
                        <li>Enter the code on the official SmartHire website only</li>
                        <li>Contact support if you didn\'t request this reset</li>
                    </ul>
                </div>
                <div class="footer">
                    <p>© 2024 SmartHire. All rights reserved.</p>
                    <p>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>';
    }
}