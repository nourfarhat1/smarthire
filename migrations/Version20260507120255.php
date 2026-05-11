<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507120255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set database timezone to Europe/Berlin';
    }

    public function up(Schema $schema): void
    {
        // Set session timezone to Europe/Berlin (UTC+1, +2 during DST)
        // Using offset format as named timezones require populated timezone tables
        $this->addSql("SET SESSION time_zone = '+01:00'");
    }

    public function down(Schema $schema): void
    {
        // Revert to system timezone
        $this->addSql("SET SESSION time_zone = 'SYSTEM'");
    }
}
