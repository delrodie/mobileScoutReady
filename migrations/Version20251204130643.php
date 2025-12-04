<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204130643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE autorisation_pointage_activite_activite DROP FOREIGN KEY FK_3537054E9B0F88B1');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_activite DROP FOREIGN KEY FK_3537054E3083A4EA');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_scout DROP FOREIGN KEY FK_14C224323083A4EA');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_scout DROP FOREIGN KEY FK_14C22432486EE6BB');
        $this->addSql('DROP TABLE autorisation_pointage_activite_activite');
        $this->addSql('DROP TABLE autorisation_pointage_activite_scout');
        $this->addSql('ALTER TABLE autorisation_pointage_activite ADD scout_id INT DEFAULT NULL, ADD activite_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE autorisation_pointage_activite ADD CONSTRAINT FK_7DE00B1C486EE6BB FOREIGN KEY (scout_id) REFERENCES scout (id)');
        $this->addSql('ALTER TABLE autorisation_pointage_activite ADD CONSTRAINT FK_7DE00B1C9B0F88B1 FOREIGN KEY (activite_id) REFERENCES activite (id)');
        $this->addSql('CREATE INDEX IDX_7DE00B1C486EE6BB ON autorisation_pointage_activite (scout_id)');
        $this->addSql('CREATE INDEX IDX_7DE00B1C9B0F88B1 ON autorisation_pointage_activite (activite_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE autorisation_pointage_activite_activite (autorisation_pointage_activite_id INT NOT NULL, activite_id INT NOT NULL, INDEX IDX_3537054E3083A4EA (autorisation_pointage_activite_id), INDEX IDX_3537054E9B0F88B1 (activite_id), PRIMARY KEY(autorisation_pointage_activite_id, activite_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE autorisation_pointage_activite_scout (autorisation_pointage_activite_id INT NOT NULL, scout_id INT NOT NULL, INDEX IDX_14C224323083A4EA (autorisation_pointage_activite_id), INDEX IDX_14C22432486EE6BB (scout_id), PRIMARY KEY(autorisation_pointage_activite_id, scout_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_activite ADD CONSTRAINT FK_3537054E9B0F88B1 FOREIGN KEY (activite_id) REFERENCES activite (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_activite ADD CONSTRAINT FK_3537054E3083A4EA FOREIGN KEY (autorisation_pointage_activite_id) REFERENCES autorisation_pointage_activite (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_scout ADD CONSTRAINT FK_14C224323083A4EA FOREIGN KEY (autorisation_pointage_activite_id) REFERENCES autorisation_pointage_activite (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE autorisation_pointage_activite_scout ADD CONSTRAINT FK_14C22432486EE6BB FOREIGN KEY (scout_id) REFERENCES scout (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE autorisation_pointage_activite DROP FOREIGN KEY FK_7DE00B1C486EE6BB');
        $this->addSql('ALTER TABLE autorisation_pointage_activite DROP FOREIGN KEY FK_7DE00B1C9B0F88B1');
        $this->addSql('DROP INDEX IDX_7DE00B1C486EE6BB ON autorisation_pointage_activite');
        $this->addSql('DROP INDEX IDX_7DE00B1C9B0F88B1 ON autorisation_pointage_activite');
        $this->addSql('ALTER TABLE autorisation_pointage_activite DROP scout_id, DROP activite_id');
    }
}
