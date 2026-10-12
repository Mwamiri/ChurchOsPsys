<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261012062450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alerts (id INT UNSIGNED AUTO_INCREMENT NOT NULL, centre_id INT UNSIGNED DEFAULT NULL, user_id INT UNSIGNED DEFAULT NULL, level VARCHAR(10) NOT NULL, title VARCHAR(191) NOT NULL, message LONGTEXT DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, is_read SMALLINT NOT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_F77AC06BDA46F468B8E8428 (is_read, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audit_logs (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, user_name VARCHAR(150) DEFAULT NULL, centre_id INT UNSIGNED DEFAULT NULL, action VARCHAR(30) NOT NULL, entity VARCHAR(50) DEFAULT NULL, entity_id INT UNSIGNED DEFAULT NULL, details LONGTEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at DATETIME DEFAULT NULL, prev_hash CHAR(64) DEFAULT NULL, hash CHAR(64) DEFAULT NULL, INDEX IDX_D62F28588B8E8428 (created_at), INDEX IDX_D62F2858E28446881257D5D (entity, entity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE centres (id INT UNSIGNED AUTO_INCREMENT NOT NULL, level SMALLINT UNSIGNED NOT NULL, name VARCHAR(191) NOT NULL, code VARCHAR(30) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(191) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, header_text LONGTEXT DEFAULT NULL, footer_text LONGTEXT DEFAULT NULL, is_active SMALLINT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, parent_id INT UNSIGNED DEFAULT NULL, UNIQUE INDEX UNIQ_3BA7EA5277153098 (code), INDEX IDX_3BA7EA52727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE login_history (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, username VARCHAR(191) DEFAULT NULL, success SMALLINT NOT NULL, reason VARCHAR(100) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, browser VARCHAR(60) DEFAULT NULL, platform VARCHAR(60) DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_37976E36A76ED3958B8E8428 (user_id, created_at), INDEX IDX_37976E368B8E8428 (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE settings (`key` VARCHAR(100) NOT NULL, value LONGTEXT DEFAULT NULL, PRIMARY KEY (`key`)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, username VARCHAR(60) NOT NULL, email VARCHAR(191) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, is_active SMALLINT NOT NULL, failed_attempts SMALLINT UNSIGNED NOT NULL, locked_until DATETIME DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, last_login_ip VARCHAR(45) DEFAULT NULL, totp_secret LONGTEXT DEFAULT NULL, totp_recovery LONGTEXT DEFAULT NULL, reset_hash VARCHAR(64) DEFAULT NULL, reset_expires DATETIME DEFAULT NULL, locale VARCHAR(5) DEFAULT NULL, prefs LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, centre_id INT UNSIGNED DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), INDEX IDX_1483A5E9463CD7C3 (centre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE centres ADD CONSTRAINT FK_3BA7EA52727ACA70 FOREIGN KEY (parent_id) REFERENCES centres (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9463CD7C3 FOREIGN KEY (centre_id) REFERENCES centres (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centres DROP FOREIGN KEY FK_3BA7EA52727ACA70');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9463CD7C3');
        $this->addSql('DROP TABLE alerts');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE centres');
        $this->addSql('DROP TABLE login_history');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE users');
    }
}
