<?php

namespace App\DataFixtures;

use App\Entity\ActivityLog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Auto-generated from database (6 row(s)) on 2026-05-25T14:35:33+00:00.
 * Regenerate: php bin/console app:fixtures:export-from-database
 */
class ActivityLogFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
  $log = new ActivityLog();
  $log->setUser(null);
  $log->setRole('SYSTEM');
  $log->setAction('CREATE');
  $log->setDescription('Created  User (Admin User, admin@gmail.com, ROLE_ADMIN) #1');
  $log->setUsername('SYSTEM');
  $log->setTargetData('User: Admin User (admin@gmail.com) (ID: 1)');
  $log->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
  $manager->persist($log);
  $this->addReference('activity_log_1', $log);  $log = new ActivityLog();
  $log->setUser(null);
  $log->setRole('SYSTEM');
  $log->setAction('CREATE');
  $log->setDescription('Created  User (User Test, user123@gmail.com, ROLE_USER) #2');
  $log->setUsername('SYSTEM');
  $log->setTargetData('User: User Test (user123@gmail.com) (ID: 2)');
  $log->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
  $manager->persist($log);
  $this->addReference('activity_log_2', $log);  $log = new ActivityLog();
  $log->setUser(null);
  $log->setRole('SYSTEM');
  $log->setAction('CREATE');
  $log->setDescription('Created  User (Event Organizer, organizer1@gmail.com, ROLE_ORGANIZER) #3');
  $log->setUsername('SYSTEM');
  $log->setTargetData('User: Event Organizer (organizer1@gmail.com) (ID: 3)');
  $log->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
  $manager->persist($log);
  $this->addReference('activity_log_3', $log);  $log = new ActivityLog();
  $log->setUser(null);
  $log->setRole('SYSTEM');
  $log->setAction('CREATE');
  $log->setDescription('Created  Event (Reiki Sound Bath) #1');
  $log->setUsername('SYSTEM');
  $log->setTargetData('Event: Reiki Sound Bath (ID: 1)');
  $log->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
  $manager->persist($log);
  $this->addReference('activity_log_4', $log);  $log = new ActivityLog();
  $log->setUser(null);
  $log->setRole('SYSTEM');
  $log->setAction('CREATE');
  $log->setDescription('Created  Event (Moonlight Tarot Circle) #2');
  $log->setUsername('SYSTEM');
  $log->setTargetData('Event: Moonlight Tarot Circle (ID: 2)');
  $log->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
  $manager->persist($log);
  $this->addReference('activity_log_5', $log);  $log = new ActivityLog();
  $log->setUser(null);
  $log->setRole('SYSTEM');
  $log->setAction('CREATE');
  $log->setDescription('Created  Event (Crystal Grid Workshop) #3');
  $log->setUsername('SYSTEM');
  $log->setTargetData('Event: Crystal Grid Workshop (ID: 3)');
  $log->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
  $manager->persist($log);
  $this->addReference('activity_log_6', $log);
        $manager->flush();
    }


    public function getDependencies(): array
    {
        return [\App\DataFixtures\UserFixture::class];
    }
}
