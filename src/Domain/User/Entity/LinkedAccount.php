<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\User\Enum\OAuthProvider;
use App\Infrastructure\User\Repository\LinkedAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Stores an external OAuth provider account linked to a User.
 *
 * Domain layer (User bounded context). Allows a single user to authenticate via
 * multiple OAuth providers (Google, Apple, Facebook) without losing data.
 * Each LinkedAccount maps one provider + provider user ID to one User.
 *
 * Created when a user first authenticates via OAuth (POST /api/auth/oauth)
 * or links an additional provider (POST /api/auth/link-account).
 *
 * Unique constraint on (provider, provider_user_id) ensures one provider account
 * can only be linked to one User.
 */
#[ORM\Entity(repositoryClass: LinkedAccountRepository::class)]
#[ORM\Table(name: 'linked_accounts')]
#[ORM\UniqueConstraint(name: 'uniq_provider_user', columns: ['provider', 'provider_user_id'])]
#[ORM\Index(name: 'idx_linked_account_user', columns: ['user_id'])]
class LinkedAccount
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 20, enumType: OAuthProvider::class)]
    private OAuthProvider $provider;

    #[ORM\Column(type: 'string', length: 255)]
    private string $providerUserId;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $linkedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->linkedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getProvider(): OAuthProvider
    {
        return $this->provider;
    }

    public function setProvider(OAuthProvider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProviderUserId(): string
    {
        return $this->providerUserId;
    }

    public function setProviderUserId(string $providerUserId): self
    {
        $this->providerUserId = $providerUserId;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLinkedAt(): \DateTimeImmutable
    {
        return $this->linkedAt;
    }

    public function __toString(): string
    {
        return $this->provider->value . ': ' . $this->email;
    }
}
