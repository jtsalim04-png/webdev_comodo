<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241201000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns to Event, User, ActivityLog, and Ticket tables';
    }

    public function up(Schema $schema): void
    {
        // Add missing columns to event table (event is a reserved word, use backticks)
        $this->addSql('ALTER TABLE `event` ADD location VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `event` ADD capacity INT NOT NULL');
        $this->addSql('ALTER TABLE `event` ADD price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE `event` ADD description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `event` ADD event_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE `event` ADD created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE `event` ADD organizer_id INT NOT NULL');
        $this->addSql('ALTER TABLE `event` ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `event` ADD CONSTRAINT FK_3BAE0AA7876C4DDA FOREIGN KEY (organizer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `event` ADD CONSTRAINT FK_3BAE0AA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');

        // Add is_active column to user table (user is also a reserved word)
        $this->addSql('ALTER TABLE `user` ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');

        // Add username and target_data columns to activity_log table
        $this->addSql('ALTER TABLE activity_log ADD username VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE activity_log ADD target_data VARCHAR(500) DEFAULT NULL');

        // Add missing columns to ticket table
        $this->addSql('ALTER TABLE ticket ADD qr_code_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD status VARCHAR(50) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE ticket ADD created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE ticket ADD purchase_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE ticket ADD price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE ticket ADD event_id INT NOT NULL');
        $this->addSql('ALTER TABLE ticket ADD customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA371F7E88B FOREIGN KEY (event_id) REFERENCES `event` (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA39395C3F3 FOREIGN KEY (customer_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign keys first
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA371F7E88B');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA39395C3F3');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7876C4DDA');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7B03A8386');

        // Remove columns from event table
        $this->addSql('ALTER TABLE `event` DROP location');
        $this->addSql('ALTER TABLE `event` DROP capacity');
        $this->addSql('ALTER TABLE `event` DROP price');
        $this->addSql('ALTER TABLE `event` DROP description');
        $this->addSql('ALTER TABLE `event` DROP event_date');
        $this->addSql('ALTER TABLE `event` DROP created_at');
        $this->addSql('ALTER TABLE `event` DROP organizer_id');
        $this->addSql('ALTER TABLE `event` DROP created_by_id');

        // Remove is_active column from user table
        $this->addSql('ALTER TABLE `user` DROP is_active');

        // Remove username and target_data columns from activity_log table
        $this->addSql('ALTER TABLE activity_log DROP username');
        $this->addSql('ALTER TABLE activity_log DROP target_data');

        // Remove columns from ticket table
        $this->addSql('ALTER TABLE ticket DROP qr_code_path');
        $this->addSql('ALTER TABLE ticket DROP status');
        $this->addSql('ALTER TABLE ticket DROP created_at');
        $this->addSql('ALTER TABLE ticket DROP purchase_date');
        $this->addSql('ALTER TABLE ticket DROP price');
        $this->addSql('ALTER TABLE ticket DROP event_id');
        $this->addSql('ALTER TABLE ticket DROP customer_id');
    }
}
