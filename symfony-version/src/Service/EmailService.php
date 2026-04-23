<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EmailService
{
    private HttpClientInterface $client;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        string $senderEmail = 'redcirclemxxn2001@gmail.com',
        string $senderName = 'SmartHire'
    ) {
        $this->client = HttpClient::create();
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    /**
     * Sends an OTP code via email using SendGrid API
     */
    public function sendOtp(string $toEmail, string $otpCode): void
    {
        $payload = [
            'personalizations' => [
                [
                    'to' => [['email' => $toEmail]],
                    'subject' => 'SmartHire — Your Password Reset Code'
                ]
            ],
            'from' => [
                'email' => $this->senderEmail,
                'name' => $this->senderName
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => "
                        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 30px; border: 1px solid #eee; border-radius: 8px;'>
                            <h2 style='color: #333;'>Password Reset Request</h2>
                            <p style='color: #555;'>You requested a password reset for your SmartHire account.</p>
                            <p style='color: #555;'>Your verification code is:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <span style='font-size: 2.5rem; font-weight: bold; letter-spacing: 0.5rem; color: #0d6efd; background: #f0f4ff; padding: 15px 30px; border-radius: 8px;'>
                                    {$otpCode}
                                </span>
                            </div>
                            <p style='color: #888; font-size: 0.9rem;'>This code is valid for <strong>10 minutes</strong>.</p>
                            <p style='color: #888; font-size: 0.9rem;'>If you did not request this, please ignore this email.</p>
                            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='color: #aaa; font-size: 0.8rem; text-align: center;'>— The SmartHire Team</p>
                        </div>
                    "
                ]
            ]
        ];

        $response = $this->client->request('POST', 'https://api.sendgrid.com/v3/mail/send', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $payload
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            $body = $response->getContent(false);
            throw new \Exception('SendGrid API error (' . $statusCode . '): ' . $body);
        }
    }
}