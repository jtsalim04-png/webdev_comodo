<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public const ADMIN_EMAIL = 'admin@gmail.com';
    public const ADMIN_LOGIN_USERNAME = 'admin';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByLoginIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        if (strcasecmp($identifier, self::ADMIN_LOGIN_USERNAME) === 0) {
            return $this->findAdminAccount();
        }

        return $this->findOneBy(['email' => $identifier]);
    }

    public function findAdminAccount(): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->andWhere('u.role IN (:roles)')
            ->setParameter('email', self::ADMIN_EMAIL)
            ->setParameter('roles', ['ROLE_ADMIN', 'admin', 'Admin'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', $role)
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

