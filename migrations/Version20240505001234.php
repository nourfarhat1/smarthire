<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240505001234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Google OAuth fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD google_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6490A61379D ON app_user (google_id)');
        $this->addSql('ALTER TABLE app_user ADD google_access_token TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD google_refresh_token TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D6490A61379D ON app_user');
        $this->addSql('ALTER TABLE app_user DROP google_id');
        $this->addSql('ALTER TABLE app_user DROP google_access_token');
        $this->addSql('ALTER TABLE app_user DROP google_refresh_token');
    }
}
