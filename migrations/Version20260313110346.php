<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313110346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY `FK_FD06F647A76ED395`');
        $this->addSql('DROP INDEX IDX_FD06F647A76ED395 ON activity_log');
        $this->addSql('ALTER TABLE activity_log ADD event_message LONGTEXT NOT NULL, DROP description, DROP user_id, CHANGE action event_type VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log ADD description LONGTEXT DEFAULT NULL, ADD user_id INT DEFAULT NULL, DROP event_message, CHANGE event_type action VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT `FK_FD06F647A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_FD06F647A76ED395 ON activity_log (user_id)');
    }
}
