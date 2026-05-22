<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixture extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(User::class);

        $admin = $repository->findOneBy(['email' => UserRepository::ADMIN_EMAIL]);
        if (!$admin) {
            $admin = new User();
            $admin->setEmail(UserRepository::ADMIN_EMAIL);
        }

        $admin->setRole('ROLE_ADMIN');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $admin->setIsActive(true);
        $admin->setIsVerified(true);

        $manager->persist($admin);

        $user = $repository->findOneBy(['email' => 'user123@gmail.com']);
        if (!$user) {
            $user = new User();
            $user->setEmail('user123@gmail.com');
        }

        $user->setRole('ROLE_USER');
        $user->setFirstName('User');
        $user->setLastName('Test');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $user->setIsActive(true);
        $user->setIsVerified(true);

        $manager->persist($user);
        $manager->flush();
    }
}
