<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Auto-generated from database (3 row(s)) on 2026-05-25T14:35:33+00:00.
 * Regenerate: php bin/console app:fixtures:export-from-database
 */
class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
$user = new User();
$user->setEmail('admin@gmail.com');
$user->setRole('ROLE_ADMIN');
$user->setFirstName('Admin');
$user->setLastName('User');
$user->setPassword('$2y$13$zWVHwMtXGFY5H9zkLJ1MKOEaNch6HsNiUCZt8Gpp.lbSOCrk.m5HK');
$user->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:00'));
$user->setIsActive((bool) 1);
$user->setIsVerified((bool) 1);
$user->setVerificationToken(null);
$manager->persist($user);
$this->addReference('user_1', $user);  $user = new User();
$user->setEmail('user123@gmail.com');
$user->setRole('ROLE_USER');
$user->setFirstName('User');
$user->setLastName('Test');
$user->setPassword('$2y$13$BJEPIKHggVTs2j3JPMNDNuqvGoWIYQ2chC63WRWfv2mLOKwS44eIW');
$user->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:01'));
$user->setIsActive((bool) 1);
$user->setIsVerified((bool) 1);
$user->setVerificationToken(null);
$manager->persist($user);
$this->addReference('user_2', $user);  $user = new User();
$user->setEmail('organizer1@gmail.com');
$user->setRole('ROLE_ORGANIZER');
$user->setFirstName('Event');
$user->setLastName('Organizer');
$user->setPassword('$2y$13$5nEzrtviri9KAzsd0wKs4.yNDYk6lEwdwAmVFls6fwJjlBCaQkrF2');
$user->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
$user->setIsActive((bool) 1);
$user->setIsVerified((bool) 1);
$user->setVerificationToken(null);
$manager->persist($user);
$this->addReference('user_3', $user);
        $manager->flush();
    }

}
