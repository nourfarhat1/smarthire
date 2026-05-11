<?php

// Test the actual registration flow step by step
require_once 'vendor/autoload.php';

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactory;
use App\Entity\User;
use App\Form\RegistrationFormType;

echo "=== Registration Flow Test ===\n\n";

// Create a test request
$request = Request::create('/candidate/auth/register', 'POST', [
    'registration_form' => [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test_' . time() . '@example.com',
        'phoneNumber' => '12345678',
        'plainPassword' => 'Password123',
        'roleId' => '1',
    ]
]);

echo "📝 Test request created:\n";
echo "   - Name: Test User\n";
echo "   - Email: " . $request->request->get('registration_form')['email'] . "\n";
echo "   - Phone: 12345678\n";
echo "   - Password: Password123\n";
echo "   - Role: 1 (Candidate)\n\n";

// Test User entity creation
echo "👤 Testing User entity creation...\n";
try {
    $user = new User();
    echo "✅ User entity created\n";
    
    // Set basic properties
    $user->setFirstName('Test');
    $user->setLastName('User');
    $user->setEmail($request->request->get('registration_form')['email']);
    $user->setPhoneNumber('12345678');
    $user->setPassword('Password123');
    $user->setRoleId(1);
    $user->setRoles(['ROLE_CANDIDATE']);
    $user->setVerified(false);
    
    echo "✅ User properties set\n";
    echo "   - Full name: " . $user->getFullName() . "\n";
    echo "   - Role: " . $user->getRoleName() . "\n";
    echo "   - Email: " . $user->getEmail() . "\n";
    echo "   - Phone: " . $user->getPhoneNumber() . "\n";
    
} catch (Exception $e) {
    echo "❌ User entity error: " . $e->getMessage() . "\n";
}

// Test form validation
echo "\n📋 Testing form validation...\n";
try {
    // Simulate form validation
    $errors = [];
    
    // Test required fields
    $data = $request->request->get('registration_form');
    if (empty($data['firstName'])) $errors[] = 'First name is required';
    if (empty($data['lastName'])) $errors[] = 'Last name is required';
    if (empty($data['email'])) $errors[] = 'Email is required';
    if (empty($data['plainPassword'])) $errors[] = 'Password is required';
    if (empty($data['roleId'])) $errors[] = 'Role is required';
    
    // Test email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Test phone number
    if (!preg_match('/^\d{8}$/', $data['phoneNumber'])) {
        $errors[] = 'Phone number must be exactly 8 digits';
    }
    
    // Test password
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $data['plainPassword']) || strlen($data['plainPassword']) < 8) {
        $errors[] = 'Password must contain at least one lowercase, one uppercase, one number, and be at least 8 characters';
    }
    
    if (empty($errors)) {
        echo "✅ Form validation: PASSED\n";
    } else {
        echo "❌ Form validation: FAILED\n";
        foreach ($errors as $error) {
            echo "   - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Form validation error: " . $e->getMessage() . "\n";
}

// Test database insertion with entity
echo "\n💾 Testing database insertion with entity...\n";
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=smarthire',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $testEmail = $request->request->get('registration_form')['email'];
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM app_user WHERE email = ?");
    $stmt->execute([$testEmail]);
    
    if ($stmt->rowCount() > 0) {
        echo "❌ Email already exists in database\n";
    } else {
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO app_user (role_id, email, password, first_name, last_name, phone_number, created_at, is_verified, is_banned, roles) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 0, 0, ?)
        ");
        
        $result = $stmt->execute([
            1, // role_id
            $testEmail,
            'Password123', // plain text password (as per current implementation)
            'Test',
            'User',
            '12345678',
            json_encode(['ROLE_CANDIDATE']) // roles as JSON
        ]);
        
        if ($result) {
            $userId = $pdo->lastInsertId();
            echo "✅ User inserted successfully\n";
            echo "   - User ID: $userId\n";
            
            // Verify insertion
            $stmt = $pdo->prepare("SELECT * FROM app_user WHERE id = ?");
            $stmt->execute([$userId]);
            $insertedUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($insertedUser) {
                echo "✅ User verified in database\n";
                echo "   - Email: " . $insertedUser['email'] . "\n";
                echo "   - Name: " . $insertedUser['first_name'] . " " . $insertedUser['last_name'] . "\n";
                echo "   - Role ID: " . $insertedUser['role_id'] . "\n";
                echo "   - Roles: " . $insertedUser['roles'] . "\n";
                
                // Clean up
                $pdo->prepare("DELETE FROM app_user WHERE id = ?")->execute([$userId]);
                echo "🧹 Test user cleaned up\n";
            } else {
                echo "❌ User not found after insertion\n";
            }
        } else {
            echo "❌ User insertion failed\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database insertion error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
