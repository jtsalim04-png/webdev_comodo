<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * Global data version for web polling — stored in DB (see Version20260529120000 migration).
 */
class RealtimeVersionService
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function getVersion(): int
    {
        try {
            $version = $this->connection->fetchOne('SELECT version FROM realtime_version WHERE id = 1');

            return (int) $version;
        } catch (TableNotFoundException) {
            return 0;
        }
    }

    public function bump(): int
    {
        try {
            $version = self::now();

            $updated = $this->connection->executeStatement(
                'UPDATE realtime_version SET version = :version WHERE id = 1',
                ['version' => $version]
            );

            if ($updated === 0) {
                return $this->getVersion();
            }

            return $version;
        } catch (TableNotFoundException) {
            return 0;
        }
    }

    public function bumpForEntity(object $entity): void
    {
        if ($entity instanceof User || $entity instanceof Event || $entity instanceof Ticket) {
            $this->bump();
        }
    }

    private static function now(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
