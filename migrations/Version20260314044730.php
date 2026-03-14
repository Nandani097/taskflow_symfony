<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314044730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Keep task_id nullable (no change needed, already DEFAULT NULL in DB)
        // Add user_id to activity_log as nullable
        $this->addSql('ALTER TABLE activity_log ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F647A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FD06F647A76ED395 ON activity_log (user_id)');

        // Add is_deleted to task — fixed syntax (one DEFAULT only)
        $this->addSql('ALTER TABLE task ADD is_deleted TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F647A76ED395');
        $this->addSql('DROP INDEX IDX_FD06F647A76ED395 ON activity_log');
        $this->addSql('ALTER TABLE activity_log DROP user_id');
        $this->addSql('ALTER TABLE task DROP is_deleted');
    }
}
