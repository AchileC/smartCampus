<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
<<<<<<<< HEAD:sfapp/migrations/Version20241204165309.php
final class Version20241204165309 extends AbstractMigration
========
final class Version20241204144050 extends AbstractMigration
>>>>>>>> origin/v2-US21-accesToASList:sfapp/migrations/Version20241204144050.php
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:sfapp/migrations/Version20241204165309.php
        $this->addSql('ALTER TABLE acquisition_system ADD name VARCHAR(255) NOT NULL');
========
        $this->addSql('ALTER TABLE acquisition_system ADD state VARCHAR(255) DEFAULT NULL');
>>>>>>>> origin/v2-US21-accesToASList:sfapp/migrations/Version20241204144050.php
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:sfapp/migrations/Version20241204165309.php
        $this->addSql('ALTER TABLE acquisition_system DROP name');
========
        $this->addSql('ALTER TABLE acquisition_system DROP state');
>>>>>>>> origin/v2-US21-accesToASList:sfapp/migrations/Version20241204144050.php
    }
}
