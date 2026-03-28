<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Repository;

use App\Domain\User\Entity\LinkedAccount;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OAuthProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for LinkedAccount entities.
 *
 * Infrastructure layer (User bounded context). Provides data access for OAuth
 * linked accounts. Used by OAuthController to look up existing links and by
 * OnboardingService when checking provider associations.
 *
 * @extends ServiceEntityRepository<LinkedAccount>
 */
class LinkedAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LinkedAccount::class);
    }

    /** Persist a linked account to the database. */
    public function save(LinkedAccount $linkedAccount, bool $flush = true): void
    {
        $this->getEntityManager()->persist($linkedAccount);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** Find a linked account by provider and provider-side user ID. Used during OAuth login. */
    public function findByProviderAndUserId(OAuthProvider $provider, string $providerUserId): ?LinkedAccount
    {
        return $this->findOneBy([
            'provider' => $provider,
            'providerUserId' => $providerUserId,
        ]);
    }

    /**
     * Find all linked accounts for a given user.
     *
     * @return LinkedAccount[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * Find all linked accounts with a given email.
     *
     * @return LinkedAccount[]
     */
    public function findByEmail(string $email): array
    {
        return $this->findBy(['email' => $email]);
    }
}
