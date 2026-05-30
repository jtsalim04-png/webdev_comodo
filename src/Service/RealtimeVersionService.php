<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\DBAL\Connection;

/**
 * Global data version for web polling — stored in DB so all CRUD updates are visible across workers.
 */
class RealtimeVersionService
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getVersion(): int
    {
        $this->ensureRowExists();

        $version = $this->connection->fetchOne('SELECT version FROM realtime_version WHERE id = 1');

        return (int) $version;
    }

    public function bump(): int
    {
        $this->ensureRowExists();
        $version = self::now();

        $this->connection->executeStatement(
            'UPDATE realtime_version SET version = :version WHERE id = 1',
            ['version' => $version]
        );

        return $version;
    }

    public function bumpForEntity(object $entity): void
    {
        if ($entity instanceof User || $entity instanceof Event || $entity instanceof Ticket) {
            $this->bump();
        }
    }

    private function ensureRowExists(): void
    {
        try {
            $exists = $this->connection->fetchOne('SELECT id FROM realtime_version WHERE id = 1');
        } catch (\Throwable) {
            $this->connection->executeStatement(
                'CREATE TABLE IF NOT EXISTS realtime_version (id INT NOT NULL, version BIGINT NOT NULL, PRIMARY KEY(id))'
            );
            $exists = false;
        }

        if ($exists !== false) {
            return;
        }

        $this->connection->insert('realtime_version', [
            'id' => 1,
            'version' => self::now(),
        ]);
    }

    private static function now(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
