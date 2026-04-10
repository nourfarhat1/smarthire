<?php

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'smarthire';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Database Schema Check ===\n\n";
    
    // Check all expected tables
    $expectedTables = [
        'app_user', 'job_category', 'job_offer', 'job_request', 'interview',
        'training', 'app_event', 'event_participant', 'complaint', 'reclaim_type',
        'response', 'quiz', 'question', 'quiz_result'
    ];
    
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Expected Tables: " . count($expectedTables) . "\n";
    echo "Existing Tables: " . count($existingTables) . "\n\n";
    
    echo "=== Table Status ===\n";
    foreach ($expectedTables as $table) {
        $status = in_array($table, $existingTables) ? "✅ EXISTS" : "❌ MISSING";
        echo sprintf("%-20s %s\n", $table, $status);
    }
    
    echo "\n=== Key Column Checks ===\n";
    
    // Check critical columns
    $checks = [
        'app_user' => ['roles'],
        'complaint' => ['subject', 'type_id'],
        'response' => ['admin_id'],
        'job_offer' => ['recruiter_id', 'category_id'],
        'job_request' => ['candidate_id', 'job_offer_id'],
        'event_participant' => ['event_id', 'user_id']
    ];
    
    foreach ($checks as $table => $columns) {
        if (in_array($table, $existingTables)) {
            echo "\n$table:\n";
            $stmt = $pdo->query("SHOW COLUMNS FROM $table");
            $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($columns as $column) {
                $status = in_array($column, $existingColumns) ? "✅" : "❌";
                echo sprintf("  %-15s %s\n", $column, $status);
            }
        }
    }
    
    echo "\n=== Foreign Key Checks ===\n";
    
    // Check foreign keys
    $fkChecks = [
        'job_offer' => ['FK_288A3A4E156BE243', 'FK_288A3A4E12469DE2'],
        'job_request' => ['FK_A178380491BD8781', 'FK_A17838043481D195'],
        'complaint' => ['FK_5F2732B5A76ED395', 'FK_5F2732B5C54C8C93'],
        'response' => ['FK_3E7B0BFB642B8210'],
        'training' => ['FK_D5128A8F642B8210'],
        'app_event' => ['FK_ED7D876A876C4DDA'],
        'event_participant' => ['FK_7C16B89171F7E88B', 'FK_7C16B891A76ED395']
    ];
    
    foreach ($fkChecks as $table => $fks) {
        if (in_array($table, $existingTables)) {
            echo "\n$table:\n";
            $stmt = $pdo->query("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '$database' 
                AND TABLE_NAME = '$table'
            ");
            $existingFKs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($fks as $fk) {
                $status = in_array($fk, $existingFKs) ? "✅" : "❌";
                echo sprintf("  %-25s %s\n", $fk, $status);
            }
        }
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
