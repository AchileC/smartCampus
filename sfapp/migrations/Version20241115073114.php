<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241115073114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE acquisition_system (id INT AUTO_INCREMENT NOT NULL, room_id INT DEFAULT NULL, temperature DOUBLE PRECISION DEFAULT NULL, humidity INT DEFAULT NULL, co2 INT DEFAULT NULL, UNIQUE INDEX UNIQ_13C6162254177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE acquisition_system ADD CONSTRAINT FK_13C6162254177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('DROP TABLE rooms');
        $this->addSql('DROP INDEX UNIQ_729F519B5E237E06 ON room');
        $this->addSql('ALTER TABLE room ADD previous_state VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rooms (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE acquisition_system DROP FOREIGN KEY FK_13C6162254177093');
        $this->addSql('DROP TABLE acquisition_system');
        $this->addSql('ALTER TABLE room DROP previous_state');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_729F519B5E237E06 ON room (name)');
    }
}
