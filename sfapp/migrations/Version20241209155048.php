<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241209155048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, acquisition_system_id INT DEFAULT NULL, info VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, INDEX IDX_47CC8C9254177093 (room_id), INDEX IDX_47CC8C92331785FF (acquisition_system_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C9254177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92331785FF FOREIGN KEY (acquisition_system_id) REFERENCES acquisition_system (id)');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE acquisition_system ADD name VARCHAR(255) NOT NULL, ADD state VARCHAR(255) DEFAULT NULL, CHANGE room_id room_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_13C616225E237E06 ON acquisition_system (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, role VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9254177093');
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92331785FF');
        $this->addSql('DROP TABLE action');
        $this->addSql('DROP INDEX UNIQ_13C616225E237E06 ON acquisition_system');
        $this->addSql('ALTER TABLE acquisition_system DROP name, DROP state, CHANGE room_id room_id INT NOT NULL');
    }
}
