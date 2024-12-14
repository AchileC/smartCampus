<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241214130731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE room CHANGE cardinal_direction cardinal_direction VARCHAR(255) DEFAULT NULL, CHANGE nb_heaters nb_heaters INT DEFAULT NULL, CHANGE nb_windows nb_windows INT DEFAULT NULL, CHANGE surface surface DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE room CHANGE cardinal_direction cardinal_direction VARCHAR(255) NOT NULL, CHANGE nb_heaters nb_heaters INT NOT NULL, CHANGE nb_windows nb_windows INT NOT NULL, CHANGE surface surface DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
