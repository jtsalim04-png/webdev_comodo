<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EventFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $userRepository = $manager->getRepository(User::class);
        $organizer = $userRepository->findOneBy(['email' => 'organizer1@gmail.com']);

        if (!$organizer) {
            $organizer = new User();
            $organizer->setEmail('organizer1@gmail.com');
            $organizer->setRole('ROLE_ORGANIZER');
            $organizer->setFirstName('Event');
            $organizer->setLastName('Organizer');
            $organizer->setPassword($this->passwordHasher->hashPassword($organizer, 'organizer123'));
            $organizer->setIsActive(true);
            $organizer->setIsVerified(true);

            $manager->persist($organizer);
        }

        $this->createEvent(
            $manager,
            'Reiki Sound Bath',
            'A calming group session with sound healing and guided relaxation to help restore balance and soothe the mind.',
            new \DateTimeImmutable('+3 days 18:00'),
            'Lotus Hall',
            450.00,
            'General Admission',
            $organizer,
        );

        $this->createEvent(
            $manager,
            'Moonlight Tarot Circle',
            'An intimate tarot reading experience by candlelight with reflective journaling and community support.',
            new \DateTimeImmutable('+5 days 19:30'),
            'Moon Garden Studio',
            520.00,
            'General Admission',
            $organizer,
        );

        $this->createEvent(
            $manager,
            'Crystal Grid Workshop',
            'Build a personal crystal grid and learn practical techniques for intention-setting and energetic alignment.',
            new \DateTimeImmutable('+7 days 17:00'),
            'Sunrise Room',
            620.00,
            'Workshop Pass',
            $organizer,
        );

        $manager->flush();
    }

    private function createEvent(ObjectManager $manager, string $title, string $description, \DateTimeImmutable $eventDate, string $location, float $price, ?string $seatType, User $organizer): void
    {
        $eventRepository = $manager->getRepository(Event::class);
        if ($eventRepository->findOneBy(['title' => $title]) !== null) {
            return;
        }

        $event = new Event();
        $event->setTitle($title);
        $event->setDescription($description);
        $event->setEventDate($eventDate);
        $event->setLocation($location);
        $event->setPrice($price);
        $event->setSeatType($seatType);
        $event->setOrganizer($organizer);
        $event->setCreatedBy($organizer);

        $manager->persist($event);
    }
}
