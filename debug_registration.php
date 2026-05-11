<?php

require_once 'vendor/autoload.php';

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\RouteCollection;
use Doctrine\ORM\EntityManager;

// Simple debug script to test registration flow
echo "=== Registration Debug Script ===\n\n";

// Test database connection
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=smarthire',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Database connection: SUCCESS\n";
    
    // Check if app_user table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_user'");
    if ($stmt->rowCount() > 0) {
        echo "✅ app_user table: EXISTS\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE app_user");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Table structure:\n";
        foreach ($columns as $column) {
            echo "   - {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']}\n";
        }
    } else {
        echo "❌ app_user table: MISSING\n";
    }
    
    // Test inserting a user
    echo "\n🧪 Testing user insertion...\n";
    $testEmail = 'test_' . time() . '@example.com';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO app_user (role_id, email, password, first_name, last_name, created_at, is_verified, is_banned, roles) 
            VALUES (1, ?, ?, ?, ?, NOW(), 0, 0, '[]')
        ");
        
        $result = $stmt->execute([
            $testEmail,
            'test_password',
            'Test',
            'User'
        ]);
        
        if ($result) {
            echo "✅ User insertion: SUCCESS\n";
            $userId = $pdo->lastInsertId();
            echo "   Inserted user ID: $userId\n";
            
            // Clean up
            $pdo->prepare("DELETE FROM app_user WHERE id = ?")->execute([$userId]);
            echo "🧹 Test user cleaned up\n";
        } else {
            echo "❌ User insertion: FAILED\n";
        }
    } catch (Exception $e) {
        echo "❌ User insertion error: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "\n";
}

// Test form validation
echo "\n📝 Testing form validation...\n";

// Test phone number validation
$phoneNumbers = ['12345678', '1234567', '123456789', 'abcdefgh'];
foreach ($phoneNumbers as $phone) {
    $isValid = preg_match('/^\d{8}$/', $phone);
    echo "   Phone '$phone': " . ($isValid ? '✅ VALID' : '❌ INVALID') . "\n";
}

// Test password validation
$passwords = ['Password1', 'password1', 'PASSWORD1', 'Pass1', 'Password123'];
foreach ($passwords as $password) {
    $isValid = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password) && strlen($password) >= 8;
    echo "   Password '$password': " . ($isValid ? '✅ VALID' : '❌ INVALID') . "\n";
}

echo "\n=== Debug Complete ===\n";
