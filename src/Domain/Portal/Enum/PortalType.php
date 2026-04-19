<?php

declare(strict_types=1);

namespace App\Domain\Portal\Enum;

/**
 * Portal types per docs/vision/portals.md:
 *
 * - static:       permanent landmark portal curated by admins
 * - dynamic:      algorithmically spawned, time-limited portal
 * - user_created: portal created by a user via a PortalCreationKit item
 */
enum PortalType: string
{
    case Static = 'static';
    case Dynamic = 'dynamic';
    case UserCreated = 'user_created';
}
