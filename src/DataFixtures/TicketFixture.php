<?php

namespace App\DataFixtures;

use App\Entity\Ticket;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Auto-generated from database (0 row(s)) on 2026-05-25T14:35:33+00:00.
 * Regenerate: php bin/console app:fixtures:export-from-database
 */
class TicketFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {

        // No tickets in database at export time.

        $manager->flush();
    }


    public function getDependencies(): array
    {
        return [\App\DataFixtures\UserFixture::class, \App\DataFixtures\EventFixture::class];
    }
}
