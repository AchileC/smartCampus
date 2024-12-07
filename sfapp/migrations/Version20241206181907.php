<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241206181907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, acquisition_system_id INT DEFAULT NULL, info VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, INDEX IDX_47CC8C9254177093 (room_id), INDEX IDX_47CC8C92331785FF (acquisition_system_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C9254177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92331785FF FOREIGN KEY (acquisition_system_id) REFERENCES acquisition_system (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9254177093');
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92331785FF');
        $this->addSql('DROP TABLE action');
    }
}
