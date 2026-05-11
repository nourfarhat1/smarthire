<?php

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'smarthire';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First check table structure
    echo "=== Table Structure ===\n";
    $stmt = $pdo->query("DESCRIBE app_user");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %-20s %-10s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default']
        );
    }
    
    echo "\n=== User Data ===\n";
    $stmt = $pdo->query("SELECT * FROM app_user WHERE email LIKE '%candid%'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No users found with 'candid' in email.\n";
        
        // Show all users
        echo "\n=== All Users ===\n";
        $stmt = $pdo->query("SELECT id, email, roleId FROM app_user LIMIT 10");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("ID: %-5d Email: %-30s Role: %d\n", 
                $row['id'], 
                $row['email'], 
                $row['roleId']
            );
        }
    } else {
        foreach ($users as $user) {
            echo "User found:\n";
            foreach ($user as $key => $value) {
                if ($key === 'passwordHash') {
                    echo "$key: " . substr($value, 0, 20) . "...\n";
                } else {
                    echo "$key: $value\n";
                }
            }
            echo "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
