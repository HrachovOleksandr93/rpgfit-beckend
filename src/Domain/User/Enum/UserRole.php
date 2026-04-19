<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * Authoritative list of application roles, string-backed so the enum values
 * can be stored directly in the `users.roles` JSON column and consumed by
 * Symfony Security's role hierarchy.
 *
 * Hierarchy (see `config/packages/security.yaml`):
 *     ROLE_SUPERADMIN > ROLE_ADMIN > ROLE_TESTER > ROLE_USER
 *
 * Every authenticated user implicitly carries ROLE_USER — the other tiers
 * grant progressively wider access to the test harness.
 */
enum UserRole: string
{
    case USER = 'ROLE_USER';
    case TESTER = 'ROLE_TESTER';
    case ADMIN = 'ROLE_ADMIN';
    case SUPERADMIN = 'ROLE_SUPERADMIN';

    /**
     * Resolve an enum case from its raw `ROLE_*` string.
     *
     * @throws \ValueError when the string is not a known role identifier
     */
    public static function fromString(string $role): self
    {
        return self::from($role);
    }

    /** Human-readable label for Sonata admin / CLI output. */
    public function label(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::TESTER => 'Tester',
            self::ADMIN => 'Admin',
            self::SUPERADMIN => 'Super Admin',
        };
    }
}
