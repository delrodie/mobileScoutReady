<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216175536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) DEFAULT NULL, message LONGTEXT DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, type_cible VARCHAR(50) DEFAULT NULL, url_action VARCHAR(255) DEFAULT NULL, libelle_action VARCHAR(100) DEFAULT NULL, icone VARCHAR(255) DEFAULT NULL, est_actif TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', expire_le DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notificationlog (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, notification_id INT NOT NULL, action VARCHAR(50) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', user_agent VARCHAR(255) DEFAULT NULL, adresse_ip VARCHAR(45) DEFAULT NULL, INDEX IDX_6643071FB88E14F (utilisateur_id), INDEX IDX_6643071EF1A9D84 (notification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur_notification (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, notification_id INT NOT NULL, est_lue TINYINT(1) DEFAULT NULL, lu_le DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D6A65239FB88E14F (utilisateur_id), INDEX IDX_D6A65239EF1A9D84 (notification_id), INDEX idx_utilisateur_lue (utilisateur_id, est_lue), INDEX idx_utilisateur_cree (utilisateur_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notificationlog ADD CONSTRAINT FK_6643071FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notificationlog ADD CONSTRAINT FK_6643071EF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_notification ADD CONSTRAINT FK_D6A65239FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_notification ADD CONSTRAINT FK_D6A65239EF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notificationlog DROP FOREIGN KEY FK_6643071FB88E14F');
        $this->addSql('ALTER TABLE notificationlog DROP FOREIGN KEY FK_6643071EF1A9D84');
        $this->addSql('ALTER TABLE utilisateur_notification DROP FOREIGN KEY FK_D6A65239FB88E14F');
        $this->addSql('ALTER TABLE utilisateur_notification DROP FOREIGN KEY FK_D6A65239EF1A9D84');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE notificationlog');
        $this->addSql('DROP TABLE utilisateur_notification');
    }
}
