<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Enum\UserRole;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the {@see UserRole} enum and the role helper methods on
 * the {@see \App\Domain\User\Entity\User} entity.
 */
class UserRoleEnumTest extends TestCase
{
    public function testEnumValuesMatchSymfonyRoleStrings(): void
    {
        self::assertSame('ROLE_USER', UserRole::USER->value);
        self::assertSame('ROLE_TESTER', UserRole::TESTER->value);
        self::assertSame('ROLE_ADMIN', UserRole::ADMIN->value);
        self::assertSame('ROLE_SUPERADMIN', UserRole::SUPERADMIN->value);
    }

    public function testFromStringRoundTrip(): void
    {
        self::assertSame(UserRole::TESTER, UserRole::fromString('ROLE_TESTER'));
        self::assertSame(UserRole::ADMIN, UserRole::fromString('ROLE_ADMIN'));
    }

    public function testLabelsAreHumanReadable(): void
    {
        self::assertSame('User', UserRole::USER->label());
        self::assertSame('Tester', UserRole::TESTER->label());
        self::assertSame('Admin', UserRole::ADMIN->label());
        self::assertSame('Super Admin', UserRole::SUPERADMIN->label());
    }
}
