<?php

// Simple test script to check Resend API
$apiKey = 'REMOVED';
$testEmail = 'test@example.com';

echo "Testing Resend API...\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Test Email: " . $testEmail . "\n\n";

// Test 1: Basic API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'from' => 'onboarding@resend.dev',
    'to' => [$testEmail],
    'subject' => 'Test Email',
    'html' => '<h1>Test</h1>'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n";
echo "Response: " . $response . "\n\n";

// Test 2: Check API key validity
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/domains');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Domains Check HTTP Status: " . $httpCode . "\n";
echo "Domains Response: " . $response . "\n\n";

?>
