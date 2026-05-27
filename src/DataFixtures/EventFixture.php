<?php

namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Auto-generated from database (3 row(s)) on 2026-05-25T14:35:33+00:00.
 * Regenerate: php bin/console app:fixtures:export-from-database
 */
class EventFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
$event = new Event();
$event->setTitle('Reiki Sound Bath');
$event->setDescription('A calming group session with sound healing and guided relaxation to help restore balance and soothe the mind.');
$event->setEventDate(new \DateTime('2026-05-28 18:00:00'));
$event->setLocation('Lotus Hall');
$event->setPrice((float) 450);
$event->setSeatType('General Admission');
$event->setOrganizer($this->getReference('user_3', User::class));
$event->setCreatedBy($this->getReference('user_3', User::class));
$event->setCreatedAt(new \DateTime('2026-05-25 14:33:02'));
$manager->persist($event);
$this->addReference('event_1', $event);  $event = new Event();
$event->setTitle('Moonlight Tarot Circle');
$event->setDescription('An intimate tarot reading experience by candlelight with reflective journaling and community support.');
$event->setEventDate(new \DateTime('2026-05-30 19:30:00'));
$event->setLocation('Moon Garden Studio');
$event->setPrice((float) 520);
$event->setSeatType('General Admission');
$event->setOrganizer($this->getReference('user_3', User::class));
$event->setCreatedBy($this->getReference('user_3', User::class));
$event->setCreatedAt(new \DateTime('2026-05-25 14:33:02'));
$manager->persist($event);
$this->addReference('event_2', $event);  $event = new Event();
$event->setTitle('Crystal Grid Workshop');
$event->setDescription('Build a personal crystal grid and learn practical techniques for intention-setting and energetic alignment.');
$event->setEventDate(new \DateTime('2026-06-01 17:00:00'));
$event->setLocation('Sunrise Room');
$event->setPrice((float) 620);
$event->setSeatType('Workshop Pass');
$event->setOrganizer($this->getReference('user_3', User::class));
$event->setCreatedBy($this->getReference('user_3', User::class));
$event->setCreatedAt(new \DateTime('2026-05-25 14:33:02'));
$manager->persist($event);
$this->addReference('event_3', $event);
        $manager->flush();
    }


    public function getDependencies(): array
    {
        return [\App\DataFixtures\UserFixture::class];
    }
}
