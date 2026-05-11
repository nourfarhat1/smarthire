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

    public function __construct(string $accountSid = 'ACa0f17a211d13d9d2c2dcdf21eafdf6db', string $authToken = '98415913f4c1078227c822718d59ca90', string $fromNumber = '+16812926760')
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
