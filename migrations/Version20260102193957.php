<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260102193957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE infos_complementaire (id INT AUTO_INCREMENT NOT NULL, scout_id INT DEFAULT NULL, branche VARCHAR(16) DEFAULT NULL, formation TINYINT(1) DEFAULT NULL, stage_base_niveau1 VARCHAR(32) DEFAULT NULL, annee_base_niveau1 INT DEFAULT NULL, stage_base_niveau2 VARCHAR(32) DEFAULT NULL, annee_base_niveau2 INT DEFAULT NULL, stage_avance_niveau1 VARCHAR(32) DEFAULT NULL, annee_avance_niveau1 INT DEFAULT NULL, stage_avance_niveau2 VARCHAR(32) DEFAULT NULL, annee_avance_niveau2 INT DEFAULT NULL, stage_avance_niveau3 VARCHAR(32) DEFAULT NULL, annee_avance_niveau3 INT DEFAULT NULL, stage_avance_niveau4 VARCHAR(32) DEFAULT NULL, annee_avance_niveau4 INT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_53BD907A486EE6BB (scout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE infos_complementaire ADD CONSTRAINT FK_53BD907A486EE6BB FOREIGN KEY (scout_id) REFERENCES scout (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE infos_complementaire DROP FOREIGN KEY FK_53BD907A486EE6BB');
        $this->addSql('DROP TABLE infos_complementaire');
    }
}
