<?php

// Database connection details
$host = 'localhost';
$dbname = 'smarthire';
$username = 'root';
$password_db = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Check if job_title column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM job_request LIKE 'job_title'");
    $stmt->execute();
    $jobTitleExists = $stmt->fetch();
    
    if (!$jobTitleExists) {
        echo "Adding job_title column...\n";
        $pdo->exec("ALTER TABLE job_request ADD job_title VARCHAR(255) DEFAULT NULL");
        echo "job_title column added successfully!\n";
    } else {
        echo "job_title column already exists.\n";
    }
    
    // Check if location column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM job_request LIKE 'location'");
    $stmt->execute();
    $locationExists = $stmt->fetch();
    
    if (!$locationExists) {
        echo "Adding location column...\n";
        $pdo->exec("ALTER TABLE job_request ADD location VARCHAR(255) DEFAULT NULL");
        echo "location column added successfully!\n";
    } else {
        echo "location column already exists.\n";
    }
    
    // Check if suggested_salary column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM job_request LIKE 'suggested_salary'");
    $stmt->execute();
    $salaryExists = $stmt->fetch();
    
    if (!$salaryExists) {
        echo "Adding suggested_salary column...\n";
        $pdo->exec("ALTER TABLE job_request ADD suggested_salary DECIMAL(10,2) DEFAULT NULL");
        echo "suggested_salary column added successfully!\n";
    } else {
        echo "suggested_salary column already exists.\n";
    }
    
    // Check if categorie column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM job_request LIKE 'categorie'");
    $stmt->execute();
    $categorieExists = $stmt->fetch();
    
    if (!$categorieExists) {
        echo "Adding categorie column...\n";
        $pdo->exec("ALTER TABLE job_request ADD categorie VARCHAR(100) DEFAULT NULL");
        echo "categorie column added successfully!\n";
    } else {
        echo "categorie column already exists.\n";
    }
    
    // Check if pdf_url column exists in quiz_result table
    $stmt = $pdo->prepare("SHOW COLUMNS FROM quiz_result LIKE 'pdf_url'");
    $stmt->execute();
    $pdfUrlExists = $stmt->fetch();
    
    if (!$pdfUrlExists) {
        echo "Adding pdf_url column to quiz_result...\n";
        $pdo->exec("ALTER TABLE quiz_result ADD pdf_url VARCHAR(500) DEFAULT NULL");
        echo "pdf_url column added successfully to quiz_result!\n";
    } else {
        echo "pdf_url column already exists in quiz_result.\n";
    }
    
    echo "Database schema update completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
