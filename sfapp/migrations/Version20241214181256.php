<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241214181256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE room CHANGE cardinal_direction cardinal_direction VARCHAR(255) NOT NULL, CHANGE nb_heaters nb_heaters INT NOT NULL, CHANGE nb_windows nb_windows INT NOT NULL');
        $this->addSql('ALTER TABLE user ADD roles JSON NOT NULL COMMENT \'(DC2Type:json)\', DROP role, CHANGE username username VARCHAR(180) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON user (username)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_USERNAME ON user');
        $this->addSql('ALTER TABLE user ADD role VARCHAR(255) DEFAULT NULL, DROP roles, CHANGE username username VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE room CHANGE cardinal_direction cardinal_direction VARCHAR(255) DEFAULT NULL, CHANGE nb_heaters nb_heaters INT DEFAULT NULL, CHANGE nb_windows nb_windows INT DEFAULT NULL');
    }
}
