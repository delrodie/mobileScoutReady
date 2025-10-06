<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006223110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scout (id INT AUTO_INCREMENT NOT NULL, slug BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', matricule VARCHAR(15) DEFAULT NULL, code VARCHAR(25) DEFAULT NULL, qr_code_token VARCHAR(255) DEFAULT NULL, nom VARCHAR(32) DEFAULT NULL, prenom VARCHAR(128) DEFAULT NULL, date_naissance DATE DEFAULT NULL, sexe VARCHAR(10) DEFAULT NULL, telephone VARCHAR(15) DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, qr_code_file VARCHAR(255) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_17688164989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, total_connexion INT DEFAULT NULL, last_connected_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, scout_id INT DEFAULT NULL, telephone VARCHAR(15) DEFAULT NULL, telegram_chat_id VARCHAR(255) DEFAULT NULL, otp_code VARCHAR(255) DEFAULT NULL, otp_requested_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', role LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', total_connexion INT DEFAULT NULL, last_connected_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_connected_device VARCHAR(255) DEFAULT NULL, last_connected_ip VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B3486EE6BB (scout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3486EE6BB FOREIGN KEY (scout_id) REFERENCES scout (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3486EE6BB');
        $this->addSql('DROP TABLE scout');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
