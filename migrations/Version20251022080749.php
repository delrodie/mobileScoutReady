<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022080749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scout ADD phone_parent TINYINT(1) DEFAULT NULL, CHANGE qr_code_token qr_code_token BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_176881641BC9050B ON scout (qr_code_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_176881641BC9050B ON scout');
        $this->addSql('ALTER TABLE scout DROP phone_parent, CHANGE qr_code_token qr_code_token VARCHAR(255) DEFAULT NULL');
    }
}
