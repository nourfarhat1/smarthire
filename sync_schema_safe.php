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

echo "Starting safe database schema sync...\n";

try {
    // Disable foreign key checks
    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
    echo "Foreign key checks disabled.\n";

    // Get the schema update SQL from doctrine
    $output = [];
    $return_var = 0;
    exec('php bin/console doctrine:schema:update --dump-sql 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        // Extract SQL statements from output
        $sqlStatements = [];
        foreach ($output as $line) {
            $line = trim($line);
            if (!empty($line) && !str_starts_with($line, '[') && !str_starts_with($line, 'Updating')) {
                // Skip non-SQL lines
                if (preg_match('/^(ALTER|CREATE|DROP|RENAME)/', $line)) {
                    $sqlStatements[] = $line;
                }
            }
        }
        
        echo "Found " . count($sqlStatements) . " SQL statements to execute.\n";
        
        // Execute each SQL statement
        foreach ($sqlStatements as $i => $sql) {
            try {
                echo "Executing statement " . ($i + 1) . ": " . substr($sql, 0, 80) . "...\n";
                $conn->executeStatement($sql);
            } catch (Exception $e) {
                echo "Error executing statement: " . $e->getMessage() . "\n";
                echo "SQL: " . $sql . "\n";
            }
        }
        
        echo "Schema sync completed!\n";
    } else {
        echo "Error getting schema update SQL.\n";
        echo "Output: " . implode("\n", $output) . "\n";
    }

    // Re-enable foreign key checks
    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    echo "Foreign key checks re-enabled.\n";

} catch (Exception $e) {
    echo "Error during schema sync: " . $e->getMessage() . "\n";
    // Make sure to re-enable foreign key checks even on error
    try {
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Exception $e2) {
        echo "Could not re-enable foreign key checks: " . $e2->getMessage() . "\n";
    }
}

echo "Database schema sync process finished.\n";
