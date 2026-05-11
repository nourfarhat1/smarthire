<?php

// Debug the 422 error by simulating the exact POST request
echo "=== Debugging 422 Unprocessable Entity Error ===\n\n";

// Create a cURL request to simulate the form submission
$url = 'http://127.0.0.1:8000/register/';
$postData = [
    'registration_form' => [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test_' . time() . '@example.com',
        'phoneNumber' => '12345678',
        'plainPassword' => 'Password123',
        'roleId' => '1'
    ]
];

// Convert to URL-encoded format
$postString = http_build_query($postData, '', '&');

echo "POST Data: $postString\n\n";

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
]);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "cURL Error: $error\n\n";

// Parse response
if ($response) {
    // Separate headers from body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo "Response Headers:\n";
    echo $headers . "\n";
    
    echo "\nResponse Body:\n";
    echo $body . "\n";
    
    // Check for specific error patterns
    if (strpos($body, '422') !== false) {
        echo "\n❌ 422 Error found in response\n";
    }
    
    if (strpos($body, 'Validation') !== false) {
        echo "🔍 Validation errors found in response\n";
    }
    
    if (strpos($body, 'error') !== false) {
        echo "🔍 Error messages found in response\n";
    }
}

echo "\n=== Analysis Complete ===\n";
