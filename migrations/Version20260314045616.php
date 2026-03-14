<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314045616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // task_id stays nullable — NOT changing it to NOT NULL (existing NULL rows)
        // is_deleted already fixed directly in MySQL — registering here as done
        $this->addSql('SELECT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task CHANGE is_deleted is_deleted TINYINT DEFAULT NULL');
    }
}
