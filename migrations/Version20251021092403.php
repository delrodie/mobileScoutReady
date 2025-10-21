<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251021092403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fonction (id INT AUTO_INCREMENT NOT NULL, scout_id INT DEFAULT NULL, instance_id INT DEFAULT NULL, poste VARCHAR(128) DEFAULT NULL, detail_poste VARCHAR(255) DEFAULT NULL, branche VARCHAR(32) DEFAULT NULL, annee VARCHAR(10) DEFAULT NULL, validation TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_900D5BD486EE6BB (scout_id), INDEX IDX_900D5BD3A51721D (instance_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fonction ADD CONSTRAINT FK_900D5BD486EE6BB FOREIGN KEY (scout_id) REFERENCES scout (id)');
        $this->addSql('ALTER TABLE fonction ADD CONSTRAINT FK_900D5BD3A51721D FOREIGN KEY (instance_id) REFERENCES instance (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fonction DROP FOREIGN KEY FK_900D5BD486EE6BB');
        $this->addSql('ALTER TABLE fonction DROP FOREIGN KEY FK_900D5BD3A51721D');
        $this->addSql('DROP TABLE fonction');
    }
}
