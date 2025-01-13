<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250110130457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE acquisition_system ADD db_name VARCHAR(255) NOT NULL, ADD last_captured_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_13C61622628DE0D9 ON acquisition_system (db_name)');
        $this->addSql('ALTER TABLE room DROP previous_state, DROP previous_sensor_state');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_13C61622628DE0D9 ON acquisition_system');
        $this->addSql('ALTER TABLE acquisition_system DROP db_name, DROP last_captured_at');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE room ADD previous_state VARCHAR(255) DEFAULT NULL, ADD previous_sensor_state VARCHAR(255) DEFAULT NULL');
    }
}
