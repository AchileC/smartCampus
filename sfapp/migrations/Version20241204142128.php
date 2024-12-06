<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
<<<<<<<< HEAD:sfapp/migrations/Version20241204154201.php
final class Version20241204154201 extends AbstractMigration
========
final class Version20241204142128 extends AbstractMigration
>>>>>>>> origin/v2-US21-accesToASList:sfapp/migrations/Version20241204142128.php
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:sfapp/migrations/Version20241204154201.php
        $this->addSql('ALTER TABLE action ADD started_at DATETIME DEFAULT NULL');
========
        $this->addSql('ALTER TABLE acquisition_system CHANGE room_id room_id INT DEFAULT NULL');
>>>>>>>> origin/v2-US21-accesToASList:sfapp/migrations/Version20241204142128.php
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:sfapp/migrations/Version20241204154201.php
        $this->addSql('ALTER TABLE action DROP started_at');
========
        $this->addSql('ALTER TABLE acquisition_system CHANGE room_id room_id INT NOT NULL');
>>>>>>>> origin/v2-US21-accesToASList:sfapp/migrations/Version20241204142128.php
    }
}
