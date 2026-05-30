<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Global data version for web/mobile polling (mirrors app pull-to-refresh / focus reload).
 */
class RealtimeVersionService
{
    private const CACHE_KEY = 'comodo.realtime.version';

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getVersion(): int
    {
        return (int) $this->cache->get(self::CACHE_KEY, static function (ItemInterface $item): int {
            $item->expiresAfter(null);

            return self::now();
        });
    }

    public function bump(): int
    {
        $version = self::now();
        $this->cache->delete(self::CACHE_KEY);
        $this->cache->get(self::CACHE_KEY, static function (ItemInterface $item) use ($version): int {
            $item->expiresAfter(null);

            return $version;
        });

        return $version;
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
