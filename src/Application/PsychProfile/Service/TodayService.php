<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\Service;

use App\Application\PsychProfile\DTO\TodayResponse;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;

/**
 * Resolves whether the daily check-in is due and returns the question set.
 *
 * Application layer (PsychProfile bounded context). Read-only. Respects the
 * per-user opt-in gate: users who haven't enabled the feature get a
 * `not_opted_in` response so clients can render the OptInCard cleanly.
 *
 * TZ note (spec §1 decision 7): status is valid until the user's local
 * midnight. In beta we anchor all dates to UTC because the `users` table
 * has no `timezone` column; the app applies local-midnight rendering on
 * the client. Revisit when a user-TZ column lands.
 */
final class TodayService
{
    public function __construct(
        private readonly PsychCheckInRepository $checkInRepository,
        private readonly PsychUserProfileRepository $profileRepository,
    ) {
    }

    public function getToday(User $user): TodayResponse
    {
        $profile = $this->profileRepository->findByUser($user);

        // User never touched the feature: return a non-due response with a
        // reason the client can use to render the opt-in card.
        if ($profile === null || !$profile->isFeatureOptedIn()) {
            return new TodayResponse(
                isDue: false,
                lastStatus: null,
                statusValidUntil: null,
                nextCheckInAt: null,
                prompts: $this->prompts(),
                reason: 'not_opted_in',
            );
        }

        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $existingToday = $this->checkInRepository->findForUserOnDate($user, $today);
        $isDue = $existingToday === null;

        // Next window opens at local midnight; we surface the valid-until
        // timestamp so the client can compute a countdown.
        $nextCheckInAt = $profile->getStatusValidUntil();

        return new TodayResponse(
            isDue: $isDue,
            lastStatus: $profile->getCurrentStatus(),
            statusValidUntil: $profile->getStatusValidUntil(),
            nextCheckInAt: $nextCheckInAt,
            prompts: $this->prompts(),
            reason: $isDue ? null : 'already_checked_in',
        );
    }

    /**
     * Fixed 3-question set (spec §1 decision 6 — no rotation in beta).
     *
     * @return array<string, mixed>
     */
    private function prompts(): array
    {
        return [
            'q1' => [
                'key' => 'mood',
                'promptUa' => 'Як ти себе почуваєш прямо зараз?',
                'promptEn' => 'How are you feeling right now?',
                'options' => [
                    ['value' => 'DRAINED', 'labelUa' => 'Виснажений', 'labelEn' => 'Drained'],
                    ['value' => 'AT_EASE', 'labelUa' => 'Спокійний', 'labelEn' => 'At ease'],
                    ['value' => 'NEUTRAL', 'labelUa' => 'Рівно', 'labelEn' => 'Neutral'],
                    ['value' => 'ENERGIZED', 'labelUa' => 'Живий', 'labelEn' => 'Energized'],
                    ['value' => 'ON_EDGE', 'labelUa' => "Нап'ятий", 'labelEn' => 'On edge'],
                ],
            ],
            'q2' => [
                'key' => 'energy',
                'promptUa' => 'Скільки в тобі енергії зараз?',
                'promptEn' => 'How much energy do you have right now?',
                'options' => [
                    ['value' => 1, 'labelUa' => 'Майже нуль', 'labelEn' => 'Almost empty'],
                    ['value' => 2, 'labelUa' => 'Нижче норми', 'labelEn' => 'Low'],
                    ['value' => 3, 'labelUa' => 'Робоча', 'labelEn' => 'Okay'],
                    ['value' => 4, 'labelUa' => 'Висока', 'labelEn' => 'High'],
                    ['value' => 5, 'labelUa' => 'Переповнена', 'labelEn' => 'Buzzing'],
                ],
            ],
            'q3' => [
                'key' => 'intent',
                'promptUa' => 'Що для тебе сьогодні?',
                'promptEn' => 'What do you want from today?',
                'footerUa' => 'Всі три — валідні перемоги сьогодні.',
                'footerEn' => 'All three are valid wins today.',
                'options' => [
                    ['value' => 'REST', 'labelUa' => 'Відпочити', 'labelEn' => 'Rest'],
                    ['value' => 'MAINTAIN', 'labelUa' => 'Утримати ритм', 'labelEn' => 'Keep rhythm'],
                    ['value' => 'PUSH', 'labelUa' => 'Натиснути', 'labelEn' => 'Push'],
                ],
            ],
        ];
    }
}
