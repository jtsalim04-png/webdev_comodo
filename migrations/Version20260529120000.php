<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store realtime version in database for reliable cross-request polling';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE realtime_version (id INT NOT NULL, version BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql("INSERT INTO realtime_version (id, version) VALUES (1, UNIX_TIMESTAMP() * 1000)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE realtime_version');
    }
}
