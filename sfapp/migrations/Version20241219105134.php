<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
<<<<<<<< HEAD:sfapp/migrations/Version20241220172708.php
final class Version20241220172708 extends AbstractMigration
========
final class Version20241219105134 extends AbstractMigration
>>>>>>>> v2-US28-Pouvoir-ajuster-les-seuils-pour-les-données:sfapp/migrations/Version20241219105134.php
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:sfapp/migrations/Version20241220172708.php
========
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, recipient_id INT NOT NULL, room_id INT NOT NULL, message VARCHAR(255) NOT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_read TINYINT(1) NOT NULL, INDEX IDX_BF5476CAE92F8F78 (recipient_id), INDEX IDX_BF5476CA54177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA54177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE threshold DROP co2_critical_min, DROP co2_warning_min, DROP co2_critical_max, DROP co2_error_max');
>>>>>>>> v2-US28-Pouvoir-ajuster-les-seuils-pour-les-données:sfapp/migrations/Version20241219105134.php
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
<<<<<<<< HEAD:sfapp/migrations/Version20241220172708.php
========
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA54177093');
        $this->addSql('DROP TABLE notification');
>>>>>>>> v2-US28-Pouvoir-ajuster-les-seuils-pour-les-données:sfapp/migrations/Version20241219105134.php
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE threshold ADD co2_critical_min DOUBLE PRECISION NOT NULL, ADD co2_warning_min DOUBLE PRECISION NOT NULL, ADD co2_critical_max DOUBLE PRECISION NOT NULL, ADD co2_error_max DOUBLE PRECISION NOT NULL');
    }
}
