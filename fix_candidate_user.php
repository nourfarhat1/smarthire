<?php

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'smarthire';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set plain text password for "candidate"
    $password = 'candidate';
    
    echo "Setting plain text password for 'candidate': $password\n\n";
    
    // Update the user
    $stmt = $pdo->prepare("
        UPDATE app_user 
        SET password = :password, is_verified = 1 
        WHERE email = 'candid.ate@esprit.tn'
    ");
    
    $stmt->execute(['password' => $password]);
    
    echo "Updated user candid.ate@esprit.tn\n";
    echo "- Password set to: 'candidate'\n";
    echo "- Account verified\n\n";
    
    // Verify the update
    $stmt = $pdo->prepare("SELECT email, is_verified, password FROM app_user WHERE email = 'candid.ate@esprit.tn'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Verification:\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "\n";
    echo "Password: " . $user['password'] . "\n";
    
    echo "\nYou can now login with:\n";
    echo "Email: candid.ate@esprit.tn\n";
    echo "Password: candidate\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
