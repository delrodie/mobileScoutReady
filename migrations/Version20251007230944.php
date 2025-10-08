<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251007230944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE instance (id INT AUTO_INCREMENT NOT NULL, instance_parent_id INT DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, INDEX IDX_4230B1DE77783764 (instance_parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE instance ADD CONSTRAINT FK_4230B1DE77783764 FOREIGN KEY (instance_parent_id) REFERENCES instance (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE instance DROP FOREIGN KEY FK_4230B1DE77783764');
        $this->addSql('DROP TABLE instance');
    }
}
