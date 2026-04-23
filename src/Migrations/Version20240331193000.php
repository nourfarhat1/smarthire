<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240331193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns to app_user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD profile_picture VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD face_login_enabled TINYINT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD face_features VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP verification_token');
        $this->addSql('ALTER TABLE app_user DROP reset_token_expires_at');
        $this->addSql('ALTER TABLE app_user DROP reset_token');
        $this->addSql('ALTER TABLE app_user DROP profile_picture');
        $this->addSql('ALTER TABLE app_user DROP face_login_enabled');
        $this->addSql('ALTER TABLE app_user DROP face_features');
    }
}
