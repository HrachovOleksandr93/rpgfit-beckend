<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Enum\OAuthProvider;
use PHPUnit\Framework\TestCase;

/** Unit tests for the OAuthProvider enum. */
class OAuthProviderEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = OAuthProvider::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(OAuthProvider::Google, $cases);
        $this->assertContains(OAuthProvider::Apple, $cases);
        $this->assertContains(OAuthProvider::Facebook, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('google', OAuthProvider::Google->value);
        $this->assertSame('apple', OAuthProvider::Apple->value);
        $this->assertSame('facebook', OAuthProvider::Facebook->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(OAuthProvider::Google, OAuthProvider::from('google'));
        $this->assertSame(OAuthProvider::Apple, OAuthProvider::from('apple'));
        $this->assertSame(OAuthProvider::Facebook, OAuthProvider::from('facebook'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        OAuthProvider::from('twitter');
    }

    public function testTryFromReturnsNullForInvalid(): void
    {
        $this->assertNull(OAuthProvider::tryFrom('github'));
    }
}
