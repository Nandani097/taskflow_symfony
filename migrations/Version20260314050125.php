<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314050125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // No changes needed — DB schema already matches entity mapping
        // task_id stays nullable because 5 orphan rows exist with NULL task_id
        $this->addSql('SELECT 1');
    }

    public function down(Schema $schema): void
    {
        // nothing to revert
    }
}
