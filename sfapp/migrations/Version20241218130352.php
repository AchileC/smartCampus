<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241218130352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE acquisition_system (id INT AUTO_INCREMENT NOT NULL, room_id INT DEFAULT NULL, temperature DOUBLE PRECISION DEFAULT NULL, humidity INT DEFAULT NULL, co2 INT DEFAULT NULL, name VARCHAR(255) NOT NULL, state VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_13C616225E237E06 (name), UNIQUE INDEX UNIQ_13C6162254177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, acquisition_system_id INT DEFAULT NULL, info VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, INDEX IDX_47CC8C9254177093 (room_id), INDEX IDX_47CC8C92331785FF (acquisition_system_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, recipient_id INT NOT NULL, room_id INT NOT NULL, message VARCHAR(255) NOT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_read TINYINT(1) NOT NULL, INDEX IDX_BF5476CAE92F8F78 (recipient_id), INDEX IDX_BF5476CA54177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE room (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, floor VARCHAR(255) NOT NULL, state VARCHAR(255) DEFAULT NULL, previous_state VARCHAR(255) DEFAULT NULL, sensor_state VARCHAR(255) DEFAULT NULL, previous_sensor_state VARCHAR(255) DEFAULT NULL, cardinal_direction VARCHAR(255) NOT NULL, nb_heaters INT NOT NULL, nb_windows INT NOT NULL, surface DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE acquisition_system ADD CONSTRAINT FK_13C6162254177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C9254177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92331785FF FOREIGN KEY (acquisition_system_id) REFERENCES acquisition_system (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA54177093 FOREIGN KEY (room_id) REFERENCES room (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE acquisition_system DROP FOREIGN KEY FK_13C6162254177093');
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9254177093');
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92331785FF');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA54177093');
        $this->addSql('DROP TABLE acquisition_system');
        $this->addSql('DROP TABLE action');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE user');
    }
}
