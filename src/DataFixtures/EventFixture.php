<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EventFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Event 1
        $event1 = new Event();
        $event1->setTitle('Reiki Sound Bath');
        $event1->setDescription('A calming group session with sound healing and guided relaxation to help restore balance and soothe the mind.');
        $event1->setEventDate(new \DateTime('2026-05-28 18:00:00'));
        $event1->setLocation('Lotus Hall');
        $event1->setPrice((float) 450);
        $event1->setSeatType('General Admission');
        $event1->setOrganizer($this->getReference('user_3', User::class));
        $event1->setCreatedBy($this->getReference('user_3', User::class));
        $event1->setCreatedAt(new \DateTime('2026-05-25 14:33:02'));
        $manager->persist($event1);
        $this->addReference('event_1', $event1);
        
        // Event 2
        $event2 = new Event();
        $event2->setTitle('Moonlight Tarot Circle');
        $event2->setDescription('An intimate tarot reading experience by candlelight with reflective journaling and community support.');
        $event2->setEventDate(new \DateTime('2026-05-30 19:30:00'));
        $event2->setLocation('Moon Garden Studio');
        $event2->setPrice((float) 520);
        $event2->setSeatType('General Admission');
        $event2->setOrganizer($this->getReference('user_3', User::class));
        $event2->setCreatedBy($this->getReference('user_3', User::class));
        $event2->setCreatedAt(new \DateTime('2026-05-25 14:33:02'));
        $manager->persist($event2);
        $this->addReference('event_2', $event2);
        
        // Event 3
        $event3 = new Event();
        $event3->setTitle('Crystal Grid Workshop');
        $event3->setDescription('Build a personal crystal grid and learn practical techniques for intention-setting and energetic alignment.');
        $event3->setEventDate(new \DateTime('2026-06-01 17:00:00'));
        $event3->setLocation('Sunrise Room');
        $event3->setPrice((float) 620);
        $event3->setSeatType('Workshop Pass');
        $event3->setOrganizer($this->getReference('user_3', User::class));
        $event3->setCreatedBy($this->getReference('user_3', User::class));
        $event3->setCreatedAt(new \DateTime('2026-05-25 14:33:02'));
        $manager->persist($event3);
        $this->addReference('event_3', $event3);
        
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }
}