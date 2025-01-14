<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
<<<<<<<< HEAD:sfapp/migrations/Version20250113105649.php
final class Version20250113105649 extends AbstractMigration
========
final class Version20250114092538 extends AbstractMigration
>>>>>>>> origin/mobile:sfapp/migrations/Version20250114092538.php
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:sfapp/migrations/Version20250113105649.php
        $this->addSql('CREATE UNIQUE INDEX UNIQ_13C61622628DE0D9 ON acquisition_system (db_name)');
========
        $this->addSql('DROP INDEX UNIQ_13C61622628DE0D9 ON acquisition_system');
        $this->addSql('ALTER TABLE acquisition_system DROP db_name');
        $this->addSql('ALTER TABLE room ADD previous_state VARCHAR(255) DEFAULT NULL, ADD previous_sensor_state VARCHAR(255) DEFAULT NULL');
>>>>>>>> origin/mobile:sfapp/migrations/Version20250114092538.php
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:sfapp/migrations/Version20250113105649.php
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('DROP INDEX UNIQ_13C61622628DE0D9 ON acquisition_system');
========
        $this->addSql('ALTER TABLE acquisition_system ADD db_name VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_13C61622628DE0D9 ON acquisition_system (db_name)');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE room DROP previous_state, DROP previous_sensor_state');
>>>>>>>> origin/mobile:sfapp/migrations/Version20250114092538.php
    }
}
