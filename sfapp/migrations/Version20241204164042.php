<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241204164042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action ADD acquisition_system_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92331785FF FOREIGN KEY (acquisition_system_id) REFERENCES acquisition_system (id)');
        $this->addSql('CREATE INDEX IDX_47CC8C92331785FF ON action (acquisition_system_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92331785FF');
        $this->addSql('DROP INDEX IDX_47CC8C92331785FF ON action');
        $this->addSql('ALTER TABLE action DROP acquisition_system_id');
    }
}
