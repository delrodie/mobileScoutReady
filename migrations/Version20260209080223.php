<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209080223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur ADD pin_code VARCHAR(4) DEFAULT NULL, ADD pin_code_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD pin_code_updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP fcm_token, DROP fcm_token_updated_at, DROP device_verified, DROP device_verification_otp, DROP device_verification_otp_expiry, DROP previous_fcm_token, DROP pending_device_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur ADD fcm_token VARCHAR(500) DEFAULT NULL, ADD fcm_token_updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD device_verified TINYINT(1) DEFAULT NULL, ADD device_verification_otp VARCHAR(6) DEFAULT NULL, ADD device_verification_otp_expiry DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD previous_fcm_token VARCHAR(500) DEFAULT NULL, ADD pending_device_id VARCHAR(255) DEFAULT NULL, DROP pin_code, DROP pin_code_created_at, DROP pin_code_updated_at');
    }
}
