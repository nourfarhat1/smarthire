<?php

require_once 'vendor/autoload.php';

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

echo "Fixing User table schema...\n";

try {
    // Disable foreign key checks
    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
    echo "Foreign key checks disabled.\n";

    // Fix User table specific issues based on the schema dump
    $userTableFixes = [
        // Ensure all user table columns match the entity
        "ALTER TABLE app_user MODIFY COLUMN id INT AUTO_INCREMENT NOT NULL",
        "ALTER TABLE app_user MODIFY COLUMN role_id INT NOT NULL",
        "ALTER TABLE app_user MODIFY COLUMN email VARCHAR(180) NOT NULL",
        "ALTER TABLE app_user MODIFY COLUMN password VARCHAR(255) NOT NULL",
        "ALTER TABLE app_user MODIFY COLUMN first_name VARCHAR(255) NOT NULL",
        "ALTER TABLE app_user MODIFY COLUMN last_name VARCHAR(255) NOT NULL",
        "ALTER TABLE app_user MODIFY COLUMN phone_number VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE app_user MODIFY COLUMN is_banned TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE app_user MODIFY COLUMN created_at DATETIME NOT NULL",
        "ALTER TABLE app_user MODIFY COLUMN roles JSON NOT NULL",
        "ALTER TABLE app_user MODIFY COLUMN verification_token VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN profile_picture VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN face_login_enabled TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE app_user MODIFY COLUMN face_features VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN cv_filename VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN updated_at DATETIME DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN face_tokens JSON DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN google_id VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN google_access_token LONGTEXT DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN google_refresh_token LONGTEXT DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN otp_code VARCHAR(10) DEFAULT NULL",
        "ALTER TABLE app_user MODIFY COLUMN otp_expiry DATETIME DEFAULT NULL"
    ];

    foreach ($userTableFixes as $sql) {
        try {
            echo "Executing: " . substr($sql, 0, 60) . "...\n";
            $conn->executeStatement($sql);
        } catch (Exception $e) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }

    // Create unique index for email if it doesn't exist
    try {
        $conn->executeStatement("CREATE UNIQUE INDEX UNIQ_88BDF3E9E7927C74 ON app_user (email)");
        echo "Created unique index on email.\n";
    } catch (Exception $e) {
        echo "Email index already exists or error: " . $e->getMessage() . "\n";
    }

    // Create unique index for google_id if it doesn't exist
    try {
        $conn->executeStatement("CREATE UNIQUE INDEX UNIQ_88BDF3E976F5C865 ON app_user (google_id)");
        echo "Created unique index on google_id.\n";
    } catch (Exception $e) {
        echo "Google ID index already exists or error: " . $e->getMessage() . "\n";
    }

    // Re-enable foreign key checks
    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    echo "Foreign key checks re-enabled.\n";

    echo "User table schema fix completed!\n";

} catch (Exception $e) {
    echo "Error during User schema fix: " . $e->getMessage() . "\n";
    // Make sure to re-enable foreign key checks even on error
    try {
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Exception $e2) {
        echo "Could not re-enable foreign key checks: " . $e2->getMessage() . "\n";
    }
}

echo "User schema fix process finished.\n";
