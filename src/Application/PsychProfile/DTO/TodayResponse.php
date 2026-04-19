<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\DTO;

use App\Domain\PsychProfile\Enum\PsychStatus;

/**
 * Response for GET /api/psych/today.
 *
 * Application layer DTO. Describes whether a check-in is due today in the
 * user's local time, what the current status is, when the next window
 * opens, and the three prompts the client should render.
 *
 * When the feature is not enabled at the user level (featureOptedIn=false),
 * the service returns `isDue=false` with `reason='not_opted_in'`.
 */
final class TodayResponse
{
    /**
     * @param array<string, mixed> $prompts
     */
    public function __construct(
        public readonly bool $isDue,
        public readonly ?PsychStatus $lastStatus,
        public readonly ?\DateTimeImmutable $statusValidUntil,
        public readonly ?\DateTimeImmutable $nextCheckInAt,
        public readonly array $prompts,
        public readonly ?string $reason = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'isDue' => $this->isDue,
            'reason' => $this->reason,
            'lastStatus' => $this->lastStatus?->value,
            'lastStatusBadgeCopyUa' => $this->lastStatus?->badgeCopyUa(),
            'lastStatusBadgeCopyEn' => $this->lastStatus?->badgeCopyEn(),
            'statusValidUntil' => $this->statusValidUntil?->format(\DateTimeInterface::ATOM),
            'nextCheckInAt' => $this->nextCheckInAt?->format(\DateTimeInterface::ATOM),
            'prompts' => $this->prompts,
        ];
    }
}
