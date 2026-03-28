<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\Entity\UserTrainingPreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for UserTrainingPreference entities.
 *
 * Infrastructure layer (User bounded context). Provides data access for
 * training preferences that are collected during onboarding.
 *
 * @extends ServiceEntityRepository<UserTrainingPreference>
 */
class UserTrainingPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTrainingPreference::class);
    }

    public function save(UserTrainingPreference $entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);

        if ($flush) {
            $em->flush();
        }
    }

    public function findByUser(User $user): ?UserTrainingPreference
    {
        return $this->findOneBy(['user' => $user]);
    }
}
