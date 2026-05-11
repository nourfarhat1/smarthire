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

echo "Final schema synchronization...\n";

try {
    // Disable foreign key checks
    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
    echo "Foreign key checks disabled.\n";

    // Apply the remaining schema changes from doctrine:schema:update --dump-sql
    $schemaUpdates = [
        // User table fixes
        "DROP INDEX email ON app_user",
        
        // Event table fixes
        "ALTER TABLE app_event RENAME INDEX fk_event_organizer TO IDX_ED7D876A876C4DDA",
        
        // Complaint table fixes
        "ALTER TABLE complaint CHANGE priority priority VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE complaint CHANGE sentiment sentiment VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE complaint RENAME INDEX fk_comp_user TO IDX_5F2732B5A76ED395",
        "ALTER TABLE complaint RENAME INDEX fk_comp_type TO IDX_5F2732B5C54C8C93",
        
        // Interview table fixes
        "ALTER TABLE interview RENAME INDEX fk_interview_req TO IDX_CF1D3C34280928B8",
        
        // Job offer table fixes
        "ALTER TABLE job_offer CHANGE salary_range salary_range VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE job_offer RENAME INDEX fk_job_recruiter TO IDX_288A3A4E156BE243",
        "ALTER TABLE job_offer RENAME INDEX fk_job_category TO IDX_288A3A4E12469DE2",
        
        // Saved jobs table fixes
        "ALTER TABLE saved_jobs RENAME INDEX idx_job_offer_id TO IDX_A6AC9FD03481D195",
        "ALTER TABLE saved_jobs RENAME INDEX idx_user_id TO IDX_A6AC9FD0A76ED395",
        
        // Job request table fixes
        "ALTER TABLE job_request CHANGE cv_url cv_url VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE job_request CHANGE job_title job_title VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE job_request CHANGE location location VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE job_request CHANGE suggested_salary suggested_salary NUMERIC(10, 2) DEFAULT NULL",
        "ALTER TABLE job_request CHANGE categorie categorie VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE job_request RENAME INDEX fk_req_candidate TO IDX_A178380491BD8781",
        "ALTER TABLE job_request RENAME INDEX fk_req_job TO IDX_A17838043481D195",
        
        // Notifications table fixes
        "ALTER TABLE notifications CHANGE route route VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE notifications CHANGE route_parameters route_parameters JSON DEFAULT NULL",
        "ALTER TABLE notifications CHANGE read_at read_at DATETIME DEFAULT NULL",
        "ALTER TABLE notifications CHANGE expires_at expires_at DATETIME DEFAULT NULL",
        "ALTER TABLE notifications CHANGE data data JSON DEFAULT NULL",
        
        // Question table fixes
        "ALTER TABLE question RENAME INDEX fk_quest_quiz TO IDX_B6F7494E853CD175",
        
        // Quiz table fixes
        "ALTER TABLE quiz RENAME INDEX fk_quiz_job TO IDX_A412FA92A72C360F",
        
        // Quiz result table fixes
        "ALTER TABLE quiz_result CHANGE pdf_url pdf_url VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE quiz_result RENAME INDEX fk_res_quiz TO IDX_FE2E314A853CD175",
        "ALTER TABLE quiz_result RENAME INDEX fk_res_cand TO IDX_FE2E314A91BD8781",
        
        // Response table fixes
        "ALTER TABLE response RENAME INDEX fk_resp_complaint TO IDX_3E7B0BFBEDAE188E",
        
        // Training table fixes
        "ALTER TABLE training CHANGE video_url video_url VARCHAR(500) DEFAULT NULL"
    ];

    foreach ($schemaUpdates as $i => $sql) {
        try {
            echo "Executing statement " . ($i + 1) . ": " . substr($sql, 0, 60) . "...\n";
            $conn->executeStatement($sql);
        } catch (Exception $e) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }

    // Re-enable foreign key checks
    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    echo "Foreign key checks re-enabled.\n";

    echo "Final schema sync completed!\n";

} catch (Exception $e) {
    echo "Error during schema sync: " . $e->getMessage() . "\n";
    // Make sure to re-enable foreign key checks even on error
    try {
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Exception $e2) {
        echo "Could not re-enable foreign key checks: " . $e2->getMessage() . "\n";
    }
}

echo "Final schema sync process finished.\n";
