<?php

namespace App\DataFixtures;

use App\Entity\ActivityLog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ActivityLogFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Activity Log 1
        $log1 = new ActivityLog();
        $log1->setUser(null);
        $log1->setRole('SYSTEM');
        $log1->setAction('CREATE');
        $log1->setDescription('Created User (Admin User, admin@gmail.com, ROLE_ADMIN) #1');
        $log1->setUsername('SYSTEM');
        $log1->setTargetData('User: Admin User (admin@gmail.com) (ID: 1)');
        $log1->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
        $manager->persist($log1);
        $this->addReference('activity_log_1', $log1);
        
        // Activity Log 2
        $log2 = new ActivityLog();
        $log2->setUser(null);
        $log2->setRole('SYSTEM');
        $log2->setAction('CREATE');
        $log2->setDescription('Created User (User Test, user123@gmail.com, ROLE_USER) #2');
        $log2->setUsername('SYSTEM');
        $log2->setTargetData('User: User Test (user123@gmail.com) (ID: 2)');
        $log2->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
        $manager->persist($log2);
        $this->addReference('activity_log_2', $log2);
        
        // Activity Log 3
        $log3 = new ActivityLog();
        $log3->setUser(null);
        $log3->setRole('SYSTEM');
        $log3->setAction('CREATE');
        $log3->setDescription('Created User (Event Organizer, organizer1@gmail.com, ROLE_ORGANIZER) #3');
        $log3->setUsername('SYSTEM');
        $log3->setTargetData('User: Event Organizer (organizer1@gmail.com) (ID: 3)');
        $log3->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
        $manager->persist($log3);
        $this->addReference('activity_log_3', $log3);
        
        // Activity Log 4
        $log4 = new ActivityLog();
        $log4->setUser(null);
        $log4->setRole('SYSTEM');
        $log4->setAction('CREATE');
        $log4->setDescription('Created Event (Reiki Sound Bath) #1');
        $log4->setUsername('SYSTEM');
        $log4->setTargetData('Event: Reiki Sound Bath (ID: 1)');
        $log4->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
        $manager->persist($log4);
        $this->addReference('activity_log_4', $log4);
        
        // Activity Log 5
        $log5 = new ActivityLog();
        $log5->setUser(null);
        $log5->setRole('SYSTEM');
        $log5->setAction('CREATE');
        $log5->setDescription('Created Event (Moonlight Tarot Circle) #2');
        $log5->setUsername('SYSTEM');
        $log5->setTargetData('Event: Moonlight Tarot Circle (ID: 2)');
        $log5->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
        $manager->persist($log5);
        $this->addReference('activity_log_5', $log5);
        
        // Activity Log 6
        $log6 = new ActivityLog();
        $log6->setUser(null);
        $log6->setRole('SYSTEM');
        $log6->setAction('CREATE');
        $log6->setDescription('Created Event (Crystal Grid Workshop) #3');
        $log6->setUsername('SYSTEM');
        $log6->setTargetData('Event: Crystal Grid Workshop (ID: 3)');
        $log6->setCreatedAt(new \DateTimeImmutable('2026-05-25 14:33:02'));
        $manager->persist($log6);
        $this->addReference('activity_log_6', $log6);
        
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class, EventFixture::class];
    }
}