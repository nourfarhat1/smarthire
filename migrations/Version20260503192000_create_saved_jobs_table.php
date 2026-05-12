<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503192000_create_saved_jobs_table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create saved_jobs table and drop incorrect job_offer_user table';
    }

    public function up(Schema $schema): void
    {
        // Drop the incorrect table first
        if ($schema->hasTable('job_offer_user')) {
            $schema->dropTable('job_offer_user');
        }

        // Create the correct saved_jobs table
        $table = $schema->createTable('saved_jobs');
        $table->addColumn('job_offer_id', 'integer', ['notnull' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['job_offer_id', 'user_id']);
        $table->addIndex(['user_id'], 'IDX_SAVED_JOBS_USER_ID');
        $table->addForeignKeyConstraint('job_offer', ['job_offer_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('app_user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $schema->dropTable('saved_jobs');
        
        // Recreate the old table for rollback
        $table = $schema->createTable('job_offer_user');
        $table->addColumn('job_offer_id', 'integer', ['notnull' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['job_offer_id', 'user_id']);
        $table->addIndex(['user_id'], 'IDX_JOB_OFFER_USER_USER_ID');
        $table->addForeignKeyConstraint('job_offer', ['job_offer_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('app_user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
    }
}
