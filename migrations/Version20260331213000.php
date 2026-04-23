<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles column to app_user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP roles');
    }
}
