<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240404120000000_add_quiz_fields extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_active, created_at, updated_at fields to quiz table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE quiz ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE quiz ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz DROP is_active');
        $this->addSql('ALTER TABLE quiz DROP created_at');
        $this->addSql('ALTER TABLE quiz DROP updated_at');
    }
}
