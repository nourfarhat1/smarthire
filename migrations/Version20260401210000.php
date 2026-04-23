<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_active column to job_offer table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_offer ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_offer DROP is_active');
    }
}
