<?php

// Test form validation logic
echo "=== Form Validation Test ===\n\n";

// Test data that would come from the registration form
$testData = [
    'firstName' => 'Test',
    'lastName' => 'User', 
    'email' => 'test_' . time() . '@example.com',
    'phoneNumber' => '12345678',
    'plainPassword' => 'Password123',
    'roleId' => '1'
];

echo "📝 Testing form validation with data:\n";
foreach ($testData as $key => $value) {
    echo "   - $key: $value\n";
}

echo "\n🔍 Running validation checks...\n";

$errors = [];

// First Name validation
if (empty($testData['firstName'])) {
    $errors[] = 'First name is required';
} elseif (strlen($testData['firstName']) < 2) {
    $errors[] = 'First name must be at least 2 characters';
} elseif (strlen($testData['firstName']) > 50) {
    $errors[] = 'First name must be less than 50 characters';
} else {
    echo "✅ First name validation passed\n";
}

// Last Name validation
if (empty($testData['lastName'])) {
    $errors[] = 'Last name is required';
} elseif (strlen($testData['lastName']) < 2) {
    $errors[] = 'Last name must be at least 2 characters';
} elseif (strlen($testData['lastName']) > 50) {
    $errors[] = 'Last name must be less than 50 characters';
} else {
    echo "✅ Last name validation passed\n";
}

// Email validation
if (empty($testData['email'])) {
    $errors[] = 'Email is required';
} elseif (!filter_var($testData['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email format is invalid';
} else {
    echo "✅ Email validation passed\n";
}

// Phone Number validation
if (!empty($testData['phoneNumber'])) {
    if (!preg_match('/^\d{8}$/', $testData['phoneNumber'])) {
        $errors[] = 'Phone number must be exactly 8 digits';
    } else {
        echo "✅ Phone number validation passed\n";
    }
} else {
    echo "✅ Phone number (optional) validation passed\n";
}

// Password validation
if (empty($testData['plainPassword'])) {
    $errors[] = 'Password is required';
} elseif (strlen($testData['plainPassword']) < 8) {
    $errors[] = 'Password must be at least 8 characters';
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $testData['plainPassword'])) {
    $errors[] = 'Password must contain at least one lowercase, one uppercase, and one number';
} else {
    echo "✅ Password validation passed\n";
}

// Role validation
if (empty($testData['roleId'])) {
    $errors[] = 'Account type is required';
} elseif (!in_array($testData['roleId'], ['1', '2'])) {
    $errors[] = 'Invalid account type selected';
} else {
    echo "✅ Role validation passed\n";
}

echo "\n📊 Validation Results:\n";
if (empty($errors)) {
    echo "✅ All validations passed! Form should be valid.\n";
} else {
    echo "❌ Validation failed with errors:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
}

// Test edge cases
echo "\n🧪 Testing edge cases...\n";

$edgeCases = [
    'invalid_email' => ['email' => 'invalid-email'],
    'short_password' => ['plainPassword' => 'Pass1'],
    'no_uppercase' => ['plainPassword' => 'password123'],
    'no_lowercase' => ['plainPassword' => 'PASSWORD123'],
    'no_number' => ['plainPassword' => 'Password'],
    'long_phone' => ['phoneNumber' => '123456789'],
    'short_phone' => ['phoneNumber' => '1234567']
];

foreach ($edgeCases as $case => $data) {
    echo "Testing $case: ";
    
    if ($case === 'invalid_email' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo "❌ Correctly rejected\n";
    } elseif ($case === 'short_password' && strlen($data['plainPassword']) < 8) {
        echo "❌ Correctly rejected\n";
    } elseif ($case === 'no_uppercase' && !preg_match('/[A-Z]/', $data['plainPassword'])) {
        echo "❌ Correctly rejected\n";
    } elseif ($case === 'no_lowercase' && !preg_match('/[a-z]/', $data['plainPassword'])) {
        echo "❌ Correctly rejected\n";
    } elseif ($case === 'no_number' && !preg_match('/\d/', $data['plainPassword'])) {
        echo "❌ Correctly rejected\n";
    } elseif ($case === 'long_phone' && !preg_match('/^\d{8}$/', $data['phoneNumber'])) {
        echo "❌ Correctly rejected\n";
    } elseif ($case === 'short_phone' && !preg_match('/^\d{8}$/', $data['phoneNumber'])) {
        echo "❌ Correctly rejected\n";
    } else {
        echo "⚠️  Unexpected result\n";
    }
}

echo "\n=== Test Complete ===\n";
