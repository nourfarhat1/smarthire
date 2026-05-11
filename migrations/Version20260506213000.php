<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused reset_token and reset_token_expires_at columns';
    }

    public function up(Schema $schema): void
    {
        // Drop the unused columns
        $this->addSql('ALTER TABLE app_user DROP reset_token');
        $this->addSql('ALTER TABLE app_user DROP reset_token_expires_at');
    }

    public function down(Schema $schema): void
    {
        // Re-add the columns if rollback needed
        $this->addSql('ALTER TABLE app_user ADD reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD reset_token_expires_at DATETIME DEFAULT NULL');
    }
}
