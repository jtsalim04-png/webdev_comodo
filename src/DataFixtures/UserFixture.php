<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // User 1: Admin User
        $admin = new User();
        $admin->setEmail('admin@gmail.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRole('ROLE_ADMIN');  // Changed from setRoles to setRole
        $admin->setPassword('admin123'); // Will hash later
        $admin->setIsActive(true);
        $admin->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:00'));
        $manager->persist($admin);
        $this->addReference('user_1', $admin);
        $this->addReference('user_admin', $admin);

        // User 2: Regular User
        $user = new User();
        $user->setEmail('user123@gmail.com');
        $user->setFirstName('User');
        $user->setLastName('Test');
        $user->setRole('ROLE_USER');  // Changed from setRoles to setRole
        $user->setPassword('user123'); // Will hash later
        $user->setIsActive(true);
        $user->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:01'));
        $manager->persist($user);
        $this->addReference('user_2', $user);
        $this->addReference('user_test', $user);

        // User 3: Event Organizer
        $organizer = new User();
        $organizer->setEmail('organizer1@gmail.com');
        $organizer->setFirstName('Event');
        $organizer->setLastName('Organizer');
        $organizer->setRole('ROLE_ORGANIZER');  // Changed from setRoles to setRole
        $organizer->setPassword('user123'); // Will hash later
        $organizer->setIsActive(true);
        $organizer->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
        $manager->persist($organizer);
        $this->addReference('user_3', $organizer);
        $this->addReference('user_organizer', $organizer);

        $manager->flush();
    }
}