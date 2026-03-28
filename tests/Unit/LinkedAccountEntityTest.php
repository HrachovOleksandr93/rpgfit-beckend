<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Entity\LinkedAccount;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OAuthProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/** Unit tests for the LinkedAccount entity. */
class LinkedAccountEntityTest extends TestCase
{
    public function testConstructorGeneratesUuidAndLinkedAt(): void
    {
        $account = new LinkedAccount();

        $this->assertInstanceOf(Uuid::class, $account->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $account->getLinkedAt());
    }

    public function testSettersAndGetters(): void
    {
        $account = new LinkedAccount();
        $user = new User();

        $account->setUser($user);
        $account->setProvider(OAuthProvider::Google);
        $account->setProviderUserId('google-123');
        $account->setEmail('user@gmail.com');

        $this->assertSame($user, $account->getUser());
        $this->assertSame(OAuthProvider::Google, $account->getProvider());
        $this->assertSame('google-123', $account->getProviderUserId());
        $this->assertSame('user@gmail.com', $account->getEmail());
    }

    public function testFluentInterface(): void
    {
        $account = new LinkedAccount();
        $user = new User();

        $result = $account
            ->setUser($user)
            ->setProvider(OAuthProvider::Apple)
            ->setProviderUserId('apple-456')
            ->setEmail('user@icloud.com');

        $this->assertSame($account, $result);
    }

    public function testToString(): void
    {
        $account = new LinkedAccount();
        $user = new User();

        $account->setUser($user);
        $account->setProvider(OAuthProvider::Facebook);
        $account->setEmail('user@facebook.com');

        $this->assertSame('facebook: user@facebook.com', (string) $account);
    }

    public function testAllProviders(): void
    {
        $account = new LinkedAccount();

        foreach (OAuthProvider::cases() as $provider) {
            $account->setProvider($provider);
            $this->assertSame($provider, $account->getProvider());
        }
    }
}
