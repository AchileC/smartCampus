<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241217165421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE threshold (id INT AUTO_INCREMENT NOT NULL, heating_temp_critical_min DOUBLE PRECISION NOT NULL, heating_temp_warning_min DOUBLE PRECISION NOT NULL, heating_temp_warning_max DOUBLE PRECISION NOT NULL, heating_temp_critical_max DOUBLE PRECISION NOT NULL, non_heating_temp_critical_min DOUBLE PRECISION NOT NULL, non_heating_temp_warning_min DOUBLE PRECISION NOT NULL, non_heating_temp_warning_max DOUBLE PRECISION NOT NULL, non_heating_temp_critical_max DOUBLE PRECISION NOT NULL, hum_critical_min DOUBLE PRECISION NOT NULL, hum_warning_min DOUBLE PRECISION NOT NULL, hum_warning_max DOUBLE PRECISION NOT NULL, hum_critical_max DOUBLE PRECISION NOT NULL, co2_critical_min DOUBLE PRECISION NOT NULL, co2_warning_min DOUBLE PRECISION NOT NULL, co2_critical_max DOUBLE PRECISION NOT NULL, co2_error_max DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_13C616225E237E06 ON acquisition_system (name)');
        $this->addSql('ALTER TABLE room DROP recommendations, CHANGE nb_heaters nb_heaters INT NOT NULL, CHANGE nb_windows nb_windows INT NOT NULL, CHANGE cardinal_direction cardinal_direction VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE threshold');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('DROP INDEX UNIQ_13C616225E237E06 ON acquisition_system');
        $this->addSql('ALTER TABLE room ADD recommendations LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE cardinal_direction cardinal_direction VARCHAR(255) DEFAULT NULL, CHANGE nb_heaters nb_heaters INT DEFAULT NULL, CHANGE nb_windows nb_windows INT DEFAULT NULL');
    }
}
