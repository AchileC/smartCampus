<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
<<<<<<<< HEAD:sfapp/migrations/Version20241218130544.php
final class Version20241218130544 extends AbstractMigration
========
final class Version20241218152344 extends AbstractMigration
>>>>>>>> origin/v3-US24-notification:sfapp/migrations/Version20241218152344.php
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE threshold DROP co2_critical_min, DROP co2_warning_min, DROP co2_critical_max, DROP co2_error_max');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE threshold ADD co2_critical_min DOUBLE PRECISION NOT NULL, ADD co2_warning_min DOUBLE PRECISION NOT NULL, ADD co2_critical_max DOUBLE PRECISION NOT NULL, ADD co2_error_max DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
