<?php

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'smarthire';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, email, firstName, lastName, roleId, isVerified, isBanned, passwordHash FROM app_user WHERE email = :email");
    $stmt->execute(['email' => 'candid.ate@esprit.tn']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User found:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Name: " . $user['firstName'] . " " . $user['lastName'] . "\n";
        echo "Role ID: " . $user['roleId'] . "\n";
        echo "Verified: " . ($user['isVerified'] ? 'Yes' : 'No') . "\n";
        echo "Banned: " . ($user['isBanned'] ? 'Yes' : 'No') . "\n";
        echo "Password Hash: " . substr($user['passwordHash'], 0, 20) . "...\n";
    } else {
        echo "User not found in database.\n";
    }
    
    // Check all users for debugging
    echo "\n\nAll users in database:\n";
    $stmt = $pdo->query("SELECT id, email, firstName, lastName, roleId FROM app_user");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-5d %-30s %-20s %-15s Role:%d\n", 
            $row['id'], 
            $row['email'], 
            $row['firstName'], 
            $row['lastName'],
            $row['roleId']
        );
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
