<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\DTO;

use App\Domain\PsychProfile\Entity\PsychCheckIn;
use App\Domain\PsychProfile\Enum\PsychStatus;

/**
 * Response for POST /api/psych/check-in.
 *
 * Application layer DTO. Carries the assigned status + vector-dialect copy
 * so the client can render the summary screen without a second request.
 *
 * `xpMultiplierHint` is informational — the actual multiplier applied at
 * battle time depends on the activity context.
 */
final class CheckInResponse
{
    public function __construct(
        public readonly string $id,
        public readonly PsychStatus $assignedStatus,
        public readonly \DateTimeImmutable $statusValidUntil,
        public readonly string $badgeCopyUa,
        public readonly string $badgeCopyEn,
        public readonly float $xpMultiplierHint,
    ) {
    }

    public static function fromCheckIn(
        PsychCheckIn $checkIn,
        \DateTimeImmutable $statusValidUntil,
        float $xpMultiplierHint,
    ): self {
        return new self(
            id: $checkIn->getId()->toRfc4122(),
            assignedStatus: $checkIn->getAssignedStatus(),
            statusValidUntil: $statusValidUntil,
            badgeCopyUa: $checkIn->getAssignedStatus()->badgeCopyUa(),
            badgeCopyEn: $checkIn->getAssignedStatus()->badgeCopyEn(),
            xpMultiplierHint: $xpMultiplierHint,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assignedStatus' => $this->assignedStatus->value,
            'statusValidUntil' => $this->statusValidUntil->format(\DateTimeInterface::ATOM),
            'badgeCopyUa' => $this->badgeCopyUa,
            'badgeCopyEn' => $this->badgeCopyEn,
            'xpMultiplierHint' => $this->xpMultiplierHint,
        ];
    }
}
