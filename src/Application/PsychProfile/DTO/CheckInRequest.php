<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\DTO;

use App\Domain\PsychProfile\Enum\MoodQuadrant;
use App\Domain\PsychProfile\Enum\UserIntent;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload for POST /api/psych/check-in.
 *
 * Application layer DTO. Maps the JSON body and carries validator
 * constraints consumed by Symfony ValidatorInterface in the controller.
 *
 * When `skipped` is true, mood/energy/intent may be null; otherwise all
 * three are required per the 3-question flow (spec 2026-04-18 §2).
 */
final class CheckInRequest
{
    /**
     * Nullable — required when `skipped` is false, validated at service layer.
     */
    #[Assert\Choice(callback: [self::class, 'moodChoices'])]
    public ?string $mood = null;

    /** Nullable — required when `skipped` is false. */
    #[Assert\Range(min: 1, max: 5)]
    public ?int $energy = null;

    #[Assert\Choice(callback: [self::class, 'intentChoices'])]
    public ?string $intent = null;

    #[Assert\NotNull]
    #[Assert\Type('bool')]
    public bool $skipped = false;

    /**
     * Psych v2 (spec §1.2) — optional 1..5 session-RPE. Only accepted
     * when `skipped` is false; otherwise silently ignored at the service
     * layer.
     */
    #[Assert\Range(min: 1, max: 5)]
    public ?int $rpeScore = null;

    /** @return list<string> */
    public static function moodChoices(): array
    {
        return array_map(static fn (MoodQuadrant $case): string => $case->value, MoodQuadrant::cases());
    }

    /** @return list<string> */
    public static function intentChoices(): array
    {
        return array_map(static fn (UserIntent $case): string => $case->value, UserIntent::cases());
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->mood = isset($data['mood']) ? (string) $data['mood'] : null;
        $dto->energy = isset($data['energy']) ? (int) $data['energy'] : null;
        $dto->intent = isset($data['intent']) ? (string) $data['intent'] : null;
        $dto->skipped = (bool) ($data['skipped'] ?? false);
        $dto->rpeScore = isset($data['rpeScore']) ? (int) $data['rpeScore'] : null;

        return $dto;
    }
}
