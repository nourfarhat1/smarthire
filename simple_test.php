<?php

// Simple test to check database operations
echo "=== Simple Database Test ===\n";

try {
    // Connect to database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=smarthire',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Database connected\n";
    
    // Test inserting a user with the exact same structure as the entity
    $testEmail = 'test_' . time() . '@example.com';
    
    $sql = "INSERT INTO app_user (role_id, email, password, first_name, last_name, phone_number, created_at, is_verified, is_banned, roles) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 0, 0, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        1, // role_id
        $testEmail,
        'Password123', // plain text password
        'Test',
        'User', 
        '12345678',
        json_encode(['ROLE_CANDIDATE']) // roles as JSON
    ]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        echo "✅ User inserted with ID: $userId\n";
        
        // Verify the user exists
        $stmt = $pdo->prepare("SELECT * FROM app_user WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ User verified in database:\n";
            echo "   - ID: " . $user['id'] . "\n";
            echo "   - Email: " . $user['email'] . "\n";
            echo "   - Name: " . $user['first_name'] . " " . $user['last_name'] . "\n";
            echo "   - Role ID: " . $user['role_id'] . "\n";
            echo "   - Roles: " . $user['roles'] . "\n";
            echo "   - Created: " . $user['created_at'] . "\n";
            
            // Check if there are any constraints that might be failing
            echo "\n🔍 Checking table constraints...\n";
            $stmt = $pdo->query("SHOW CREATE TABLE app_user");
            $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Table structure:\n" . $createTable['Create Table'] . "\n";
            
            // Clean up
            $pdo->prepare("DELETE FROM app_user WHERE id = ?")->execute([$userId]);
            echo "🧹 Test user cleaned up\n";
        } else {
            echo "❌ User not found after insertion\n";
        }
    } else {
        echo "❌ Insert failed\n";
        print_r($stmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
