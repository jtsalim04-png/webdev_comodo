<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema for fresh databases (Docker / Railway).
 */
final class Version20251001000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial Comodo booking schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `user` (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL,
            password VARCHAR(100) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            is_verified TINYINT(1) DEFAULT 0 NOT NULL,
            verification_token VARCHAR(255) DEFAULT NULL,
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE `event` (
            id INT AUTO_INCREMENT NOT NULL,
            organizer_id INT NOT NULL,
            created_by_id INT DEFAULT NULL,
            title VARCHAR(150) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            event_date DATETIME NOT NULL,
            location VARCHAR(255) DEFAULT NULL,
            price DOUBLE PRECISION NOT NULL,
            seat_type VARCHAR(100) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_3BAE0AA7876C4DDA (organizer_id),
            INDEX IDX_3BAE0AA7B03A8386 (created_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE ticket (
            id INT AUTO_INCREMENT NOT NULL,
            event_id INT NOT NULL,
            customer_id INT NOT NULL,
            price NUMERIC(10, 2) NOT NULL,
            purchase_date DATETIME NOT NULL,
            qr_code_path VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) DEFAULT \'pending\' NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_97A0ADA371F7E88B (event_id),
            INDEX IDX_97A0ADA39395C3F3 (customer_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE activity_log (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            role VARCHAR(50) NOT NULL,
            action VARCHAR(100) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            username VARCHAR(255) DEFAULT NULL,
            target_data VARCHAR(500) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_FD530F92A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE `event` ADD CONSTRAINT FK_3BAE0AA7876C4DDA FOREIGN KEY (organizer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `event` ADD CONSTRAINT FK_3BAE0AA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA371F7E88B FOREIGN KEY (event_id) REFERENCES `event` (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA39395C3F3 FOREIGN KEY (customer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD530F92A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD530F92A76ED395');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA371F7E88B');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA39395C3F3');
        $this->addSql('ALTER TABLE `event` DROP FOREIGN KEY FK_3BAE0AA7876C4DDA');
        $this->addSql('ALTER TABLE `event` DROP FOREIGN KEY FK_3BAE0AA7B03A8386');
        $this->addSql('DROP TABLE activity_log');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE `event`');
        $this->addSql('DROP TABLE `user`');
    }
}
