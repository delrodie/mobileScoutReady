<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202224500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assister (id INT AUTO_INCREMENT NOT NULL, reunion_id INT DEFAULT NULL, scout_id INT DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, observation LONGTEXT DEFAULT NULL, INDEX IDX_31849FA54E9B7368 (reunion_id), INDEX IDX_31849FA5486EE6BB (scout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE autorisation_pointage_reunion (id INT AUTO_INCREMENT NOT NULL, scout_id INT DEFAULT NULL, reunion_id INT DEFAULT NULL, role VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7465133A486EE6BB (scout_id), INDEX IDX_7465133A4E9B7368 (reunion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reunion (id INT AUTO_INCREMENT NOT NULL, champs_id INT DEFAULT NULL, instance_id INT DEFAULT NULL, titre VARCHAR(255) DEFAULT NULL, objectif LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, attente LONGTEXT DEFAULT NULL, lieu VARCHAR(255) DEFAULT NULL, date_at DATE DEFAULT NULL, heure_debut TIME DEFAULT NULL, heure_fin TIME DEFAULT NULL, cible LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', branche VARCHAR(255) DEFAULT NULL, url_pointage VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_by VARCHAR(255) DEFAULT NULL, INDEX IDX_5B00A4821ABA8B (champs_id), INDEX IDX_5B00A4823A51721D (instance_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE assister ADD CONSTRAINT FK_31849FA54E9B7368 FOREIGN KEY (reunion_id) REFERENCES reunion (id)');
        $this->addSql('ALTER TABLE assister ADD CONSTRAINT FK_31849FA5486EE6BB FOREIGN KEY (scout_id) REFERENCES scout (id)');
        $this->addSql('ALTER TABLE autorisation_pointage_reunion ADD CONSTRAINT FK_7465133A486EE6BB FOREIGN KEY (scout_id) REFERENCES scout (id)');
        $this->addSql('ALTER TABLE autorisation_pointage_reunion ADD CONSTRAINT FK_7465133A4E9B7368 FOREIGN KEY (reunion_id) REFERENCES reunion (id)');
        $this->addSql('ALTER TABLE reunion ADD CONSTRAINT FK_5B00A4821ABA8B FOREIGN KEY (champs_id) REFERENCES champ_activite (id)');
        $this->addSql('ALTER TABLE reunion ADD CONSTRAINT FK_5B00A4823A51721D FOREIGN KEY (instance_id) REFERENCES instance (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assister DROP FOREIGN KEY FK_31849FA54E9B7368');
        $this->addSql('ALTER TABLE assister DROP FOREIGN KEY FK_31849FA5486EE6BB');
        $this->addSql('ALTER TABLE autorisation_pointage_reunion DROP FOREIGN KEY FK_7465133A486EE6BB');
        $this->addSql('ALTER TABLE autorisation_pointage_reunion DROP FOREIGN KEY FK_7465133A4E9B7368');
        $this->addSql('ALTER TABLE reunion DROP FOREIGN KEY FK_5B00A4821ABA8B');
        $this->addSql('ALTER TABLE reunion DROP FOREIGN KEY FK_5B00A4823A51721D');
        $this->addSql('DROP TABLE assister');
        $this->addSql('DROP TABLE autorisation_pointage_reunion');
        $this->addSql('DROP TABLE reunion');
    }
}
