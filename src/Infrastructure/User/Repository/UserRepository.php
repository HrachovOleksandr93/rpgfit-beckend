<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Repository;

use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for the User entity.
 *
 * Infrastructure layer (User bounded context). Provides data access methods for user
 * lookup and persistence. Used by RegistrationService for uniqueness checks and
 * by Symfony Security for authentication (login lookup).
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user, bool $flush = true): void
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** Find user by email login. Used for uniqueness check during registration and by Symfony Security. */
    public function findByLogin(string $login): ?User
    {
        return $this->findOneBy(['login' => $login]);
    }

    /** Find user by display name. Used for uniqueness check during registration. */
    public function findByDisplayName(string $displayName): ?User
    {
        return $this->findOneBy(['displayName' => $displayName]);
    }
}
