<?php

// Check for error logs and create a simple test
echo "=== Checking Registration Status ===\n\n";

// Check recent PHP errors
$errorLog = ini_get('error_log');
echo "PHP Error Log: $errorLog\n";

if (file_exists($errorLog)) {
    echo "Recent PHP errors:\n";
    $errors = file_get_contents($errorLog);
    $recentErrors = substr($errors, -2000); // Last 2000 characters
    echo $recentErrors . "\n";
} else {
    echo "No PHP error log found\n";
}

// Check Symfony logs
$symfonyLogDir = __DIR__ . '/var/log';
if (is_dir($symfonyLogDir)) {
    echo "\nSymfony log directory exists\n";
    $files = scandir($symfonyLogDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "Found log file: $file\n";
        }
    }
} else {
    echo "\nSymfony log directory does not exist\n";
}

// Test the registration form directly
echo "\n=== Testing Registration Form ===\n";

// Simulate form data
$formData = [
    'registration_form' => [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test_' . time() . '@example.com',
        'phoneNumber' => '12345678',
        'plainPassword' => 'Password123',
        'roleId' => '1'
    ]
];

echo "Form data prepared:\n";
foreach ($formData['registration_form'] as $key => $value) {
    echo "  $key: $value\n";
}

// Check if the form would be valid
$validationErrors = [];

// First name validation
if (empty($formData['registration_form']['firstName'])) {
    $validationErrors[] = 'First name is required';
} elseif (strlen($formData['registration_form']['firstName']) < 2) {
    $validationErrors[] = 'First name must be at least 2 characters';
}

// Last name validation
if (empty($formData['registration_form']['lastName'])) {
    $validationErrors[] = 'Last name is required';
} elseif (strlen($formData['registration_form']['lastName']) < 2) {
    $validationErrors[] = 'Last name must be at least 2 characters';
}

// Email validation
if (empty($formData['registration_form']['email'])) {
    $validationErrors[] = 'Email is required';
} elseif (!filter_var($formData['registration_form']['email'], FILTER_VALIDATE_EMAIL)) {
    $validationErrors[] = 'Invalid email format';
}

// Password validation
if (empty($formData['registration_form']['plainPassword'])) {
    $validationErrors[] = 'Password is required';
} elseif (strlen($formData['registration_form']['plainPassword']) < 8) {
    $validationErrors[] = 'Password must be at least 8 characters';
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $formData['registration_form']['plainPassword'])) {
    $validationErrors[] = 'Password must contain uppercase, lowercase, and number';
}

// Phone validation
if (!empty($formData['registration_form']['phoneNumber'])) {
    if (!preg_match('/^\d{8}$/', $formData['registration_form']['phoneNumber'])) {
        $validationErrors[] = 'Phone number must be exactly 8 digits';
    }
}

// Role validation
if (empty($formData['registration_form']['roleId'])) {
    $validationErrors[] = 'Role is required';
}

echo "\nValidation Results:\n";
if (empty($validationErrors)) {
    echo "✅ Form validation would pass\n";
} else {
    echo "❌ Form validation would fail:\n";
    foreach ($validationErrors as $error) {
        echo "  - $error\n";
    }
}

// Check database connection
echo "\n=== Checking Database ===\n";
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=smarthire',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Database connection successful\n";
    
    // Check if we can insert a test user
    $testEmail = 'test_' . time() . '@example.com';
    $stmt = $pdo->prepare("INSERT INTO app_user (role_id, email, password, first_name, last_name, phone_number, created_at, is_verified, is_banned, roles) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW(), 0, 0, ?)");
    
    $result = $stmt->execute([
        1,
        $testEmail,
        'Password123',
        'Test',
        'User',
        '12345678',
        json_encode(['ROLE_CANDIDATE'])
    ]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        echo "✅ Test user inserted with ID: $userId\n";
        
        // Clean up
        $pdo->prepare("DELETE FROM app_user WHERE id = ?")->execute([$userId]);
        echo "✅ Test user cleaned up\n";
    } else {
        echo "❌ Failed to insert test user\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
