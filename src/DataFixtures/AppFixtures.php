<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Optional group entry point. Default: doctrine:fixtures:load loads all fixtures in this folder.
 */
class AppFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // Entity data lives in UserFixture, EventFixture, TicketFixture, ActivityLogFixture.
    }

    public static function getGroups(): array
    {
        return ['app'];
    }
}
