<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129230951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activite (id INT AUTO_INCREMENT NOT NULL, instance_id INT DEFAULT NULL, titre VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, theme VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, lieu VARCHAR(255) DEFAULT NULL, date_debut_at DATE DEFAULT NULL, date_fin_at DATE DEFAULT NULL, heure_debut TIME DEFAULT NULL, heure_fin TIME DEFAULT NULL, cible VARCHAR(255) DEFAULT NULL, affiche VARCHAR(255) DEFAULT NULL, tdr VARCHAR(255) DEFAULT NULL, url_pointage VARCHAR(255) DEFAULT NULL, INDEX IDX_B87555153A51721D (instance_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE autorisation_pointage_activite (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE autorisation_pointage_activite_scout (autorisation_pointage_activite_id INT NOT NULL, scout_id INT NOT NULL, INDEX IDX_14C224323083A4EA (autorisation_pointage_activite_id), INDEX IDX_14C22432486EE6BB (scout_id), PRIMARY KEY(autorisation_pointage_activite_id, scout_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE autorisation_pointage_activite_activite (autorisation_pointage_activite_id INT NOT NULL, activite_id INT NOT NULL, INDEX IDX_3537054E3083A4EA (autorisation_pointage_activite_id), INDEX IDX_3537054E9B0F88B1 (activite_id), PRIMARY KEY(autorisation_pointage_activite_id, activite_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participer (id INT AUTO_INCREMENT NOT NULL, activite_id INT DEFAULT NULL, scout_id INT DEFAULT NULL, pointage_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', note VARCHAR(255) DEFAULT NULL, observation LONGTEXT DEFAULT NULL, INDEX IDX_EDBE16F89B0F88B1 (activite_id), INDEX IDX_EDBE16F8486EE6BB (scout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B87555153A51721D FOREIGN KEY (instance_id) REFERENCES instance (id)');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_scout ADD CONSTRAINT FK_14C224323083A4EA FOREIGN KEY (autorisation_pointage_activite_id) REFERENCES autorisation_pointage_activite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_scout ADD CONSTRAINT FK_14C22432486EE6BB FOREIGN KEY (scout_id) REFERENCES scout (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_activite ADD CONSTRAINT FK_3537054E3083A4EA FOREIGN KEY (autorisation_pointage_activite_id) REFERENCES autorisation_pointage_activite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_activite ADD CONSTRAINT FK_3537054E9B0F88B1 FOREIGN KEY (activite_id) REFERENCES activite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participer ADD CONSTRAINT FK_EDBE16F89B0F88B1 FOREIGN KEY (activite_id) REFERENCES activite (id)');
        $this->addSql('ALTER TABLE participer ADD CONSTRAINT FK_EDBE16F8486EE6BB FOREIGN KEY (scout_id) REFERENCES scout (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B87555153A51721D');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_scout DROP FOREIGN KEY FK_14C224323083A4EA');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_scout DROP FOREIGN KEY FK_14C22432486EE6BB');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_activite DROP FOREIGN KEY FK_3537054E3083A4EA');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_activite DROP FOREIGN KEY FK_3537054E9B0F88B1');
        $this->addSql('ALTER TABLE participer DROP FOREIGN KEY FK_EDBE16F89B0F88B1');
        $this->addSql('ALTER TABLE participer DROP FOREIGN KEY FK_EDBE16F8486EE6BB');
        $this->addSql('DROP TABLE activite');
        $this->addSql('DROP TABLE autorisation_pointage_activite');
        $this->addSql('DROP TABLE autorisation_pointage_activite_scout');
        $this->addSql('DROP TABLE autorisation_pointage_activite_activite');
        $this->addSql('DROP TABLE participer');
    }
}
