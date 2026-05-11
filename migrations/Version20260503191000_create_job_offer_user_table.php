<?php
// maram git version

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503191000_create_job_offer_user_table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job_offer_user join table for saved jobs functionality';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $table = $schema->createTable('job_offer_user');
        $table->addColumn('job_offer_id', 'integer', ['notnull' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['job_offer_id', 'user_id']);
        $table->addIndex(['user_id'], 'IDX_JOB_OFFER_USER_USER_ID');
        $table->addForeignKeyConstraint('job_offer', ['job_offer_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('app_user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $schema->dropTable('job_offer_user');
    }
}
