<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * Supported OAuth authentication providers.
 *
 * Used by LinkedAccount entity to identify which external provider
 * a user authenticated with. Each provider has its own SDK flow
 * on the mobile app side.
 */
enum OAuthProvider: string
{
    case Google = 'google';
    case Apple = 'apple';
    case Facebook = 'facebook';
}
