<?php

require_once 'vendor/autoload.php';

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;

// Database configuration
$dbParams = [
    'driver'   => 'pdo_mysql',
    'host'     => '127.0.0.1',
    'dbname'   => 'smarthire',
    'user'     => 'root',
    'password' => '',
];

// Create connection
$conn = DriverManager::getConnection($dbParams);

echo "Checking database sync...\n";

// Check if resetToken columns exist
$checkResetToken = $conn->executeQuery("
    SELECT COUNT(*) as count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'smarthire' 
    AND TABLE_NAME = 'app_user' 
    AND COLUMN_NAME = 'resetToken'
")->fetchOne();

$checkResetTokenExpiry = $conn->executeQuery("
    SELECT COUNT(*) as count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'smarthire' 
    AND TABLE_NAME = 'app_user' 
    AND COLUMN_NAME = 'resetTokenExpiryDate'
")->fetchOne();

echo "resetToken column exists: " . ($checkResetToken ? 'YES' : 'NO') . "\n";
echo "resetTokenExpiryDate column exists: " . ($checkResetTokenExpiry ? 'YES' : 'NO') . "\n";

// Remove columns if they exist
if ($checkResetToken > 0) {
    echo "Removing resetToken column...\n";
    $conn->executeStatement("ALTER TABLE app_user DROP COLUMN resetToken");
    echo "resetToken column removed.\n";
}

if ($checkResetTokenExpiry > 0) {
    echo "Removing resetTokenExpiryDate column...\n";
    $conn->executeStatement("ALTER TABLE app_user DROP COLUMN resetTokenExpiryDate");
    echo "resetTokenExpiryDate column removed.\n";
}

echo "Database sync completed!\n";
