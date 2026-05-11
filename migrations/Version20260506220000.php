<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync database schema with current entity mappings (remove resetToken fields if they exist)';
    }

    public function up(Schema $schema): void
    {
        // Only handle the user table resetToken fields removal
        // This avoids foreign key constraint issues with other tables
        
        // Check if resetToken column exists and drop it
        $this->addSql('SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'app_user\' AND COLUMN_NAME = \'resetToken\')');
        $this->addSql('SET @sql = IF(@column_exists > 0, \'ALTER TABLE app_user DROP COLUMN resetToken\', \'SELECT "Column resetToken does not exist"\')');
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        // Check if resetTokenExpiryDate column exists and drop it
        $this->addSql('SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'app_user\' AND COLUMN_NAME = \'resetTokenExpiryDate\')');
        $this->addSql('SET @sql = IF(@column_exists > 0, \'ALTER TABLE app_user DROP COLUMN resetTokenExpiryDate\', \'SELECT "Column resetTokenExpiryDate does not exist"\')');
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        // Add back the columns for rollback if needed
        $this->addSql('ALTER TABLE app_user ADD COLUMN resetToken VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD COLUMN resetTokenExpiryDate DATETIME DEFAULT NULL');
    }
}
