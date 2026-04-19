<?php

declare(strict_types=1);

namespace App\Infrastructure\PsychProfile\Repository;

use App\Domain\PsychProfile\Entity\PsychUserProfile;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for PsychUserProfile entities.
 *
 * Infrastructure layer (PsychProfile bounded context). Enforces the
 * "one profile per user" invariant through `findOrCreateForUser()`.
 *
 * @extends ServiceEntityRepository<PsychUserProfile>
 */
class PsychUserProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PsychUserProfile::class);
    }

    public function save(PsychUserProfile $entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        if ($flush) {
            $em->flush();
        }
    }

    public function findByUser(User $user): ?PsychUserProfile
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * Get or lazily create the single profile for the given user.
     * Does NOT flush if a new profile was created — caller decides.
     */
    public function findOrCreateForUser(User $user): PsychUserProfile
    {
        $profile = $this->findByUser($user);
        if ($profile !== null) {
            return $profile;
        }

        $profile = new PsychUserProfile();
        $profile->setUser($user);

        // Persist but let the caller flush inside its own unit-of-work.
        $this->getEntityManager()->persist($profile);

        return $profile;
    }
}
