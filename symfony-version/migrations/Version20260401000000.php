<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns to complaint table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE complaint ADD priority VARCHAR(20) DEFAULT \'MEDIUM\'');
        $this->addSql('ALTER TABLE complaint ADD ai_summary TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE complaint ADD sentiment VARCHAR(20) DEFAULT \'NEUTRAL\'');
        $this->addSql('ALTER TABLE complaint ADD urgency_score INT DEFAULT 5');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE complaint DROP priority');
        $this->addSql('ALTER TABLE complaint DROP ai_summary');
        $this->addSql('ALTER TABLE complaint DROP sentiment');
        $this->addSql('ALTER TABLE complaint DROP urgency_score');
    }
}
