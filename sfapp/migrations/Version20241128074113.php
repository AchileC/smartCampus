<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241128074113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE acquisition_system (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, temperature DOUBLE PRECISION DEFAULT NULL, humidity INT DEFAULT NULL, co2 INT DEFAULT NULL, UNIQUE INDEX UNIQ_13C6162254177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE acquisition_system ADD CONSTRAINT FK_13C6162254177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE room ADD previous_state VARCHAR(255) DEFAULT NULL, ADD previous_sensor_state VARCHAR(255) DEFAULT NULL, ADD cardinal_direction VARCHAR(255) DEFAULT NULL, ADD nb_heaters INT DEFAULT NULL, ADD nb_windows INT DEFAULT NULL, ADD surface DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, last_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, username VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, role VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE acquisition_system DROP FOREIGN KEY FK_13C6162254177093');
        $this->addSql('DROP TABLE acquisition_system');
        $this->addSql('ALTER TABLE room DROP previous_state, DROP previous_sensor_state, DROP cardinal_direction, DROP nb_heaters, DROP nb_windows, DROP surface');
    }
}
