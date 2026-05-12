<?php

require_once 'vendor/autoload.php';

// Plain text password
$password = '231jft';


// Database connection details
$host = 'localhost';
$dbname = 'smarthire';
$username = 'root';
$password_db = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update the password with plain text
    $stmt = $pdo->prepare("UPDATE app_user SET password = ? WHERE email = ?");
    $stmt->execute([$password, 'amine.nafti@esprit.tn']);
    
    echo "Password updated successfully!\n";
    echo "You can now log in with:\n";
    echo "Email: amine.nafti@esprit.tn\n";
    echo "Password: 231jft\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
