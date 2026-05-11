<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SMSService
{
    private HttpClientInterface $client;
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;

    public function __construct(string $accountSid = 'your_twilio_account_sid_here', string $authToken = 'your_twilio_auth_token_here', string $fromNumber = '+your_twilio_phone_number_here')
    {
        $this->client = HttpClient::create();
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
        $this->fromNumber = $fromNumber;
    }

    /**
     * Sends an OTP code via SMS to the given phone number.
     * Phone number must be in international format e.g. +21612345678
     */
    public function sendOtp(string $toPhoneNumber, string $otpCode): void
    {
        $messageBody = 
            "Your SmartHire password reset code is: " . $otpCode . "\n" .
            "This code expires in 10 minutes.\n" .
            "If you did not request this, please ignore this message.";

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
        
        $response = $this->client->request('POST', $url, [
            'auth_basic' => [$this->accountSid, $this->authToken],
            'body' => [
                'To' => $toPhoneNumber,
                'From' => $this->fromNumber,
                'Body' => $messageBody
            ]
        ]);

        $result = $response->toArray();
        echo "SMS sent. SID: " . ($result['sid'] ?? 'unknown');
    }
}
