<?php

declare(strict_types=1);

namespace App\Application\Character\Service;

use App\Domain\User\Enum\Lifestyle;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\User\Enum\WorkoutType;

/**
 * Calculates initial RPG character stats based on onboarding answers.
 *
 * Distributes exactly 30 total stat points across STR, DEX, CON based on
 * the user's workout type, lifestyle, and training frequency. Each factor
 * contributes bonus points on top of a base allocation of 5 per stat.
 *
 * The raw sum is normalized to exactly 30: excess points are proportionally
 * reduced, deficit points are added to the highest stat.
 */
final class StatCalculationService
{
    /** Target total for all three stats combined. */
    private const int TOTAL_POINTS = 30;

    /** Base allocation per stat before bonuses. */
    private const int BASE_PER_STAT = 5;

    /**
     * Workout type bonus matrix: [STR, DEX, CON].
     * The 'mixed' type maps to the gymnastics distribution.
     */
    private const array WORKOUT_BONUSES = [
        'strength'     => [5, 1, 2],
        'cardio'       => [1, 3, 5],
        'crossfit'     => [3, 3, 3],
        'gymnastics'   => [1, 5, 2],
        'mixed'        => [1, 5, 2], // Same as gymnastics
        'martial_arts' => [3, 4, 2],
        'yoga'         => [1, 5, 2],
    ];

    /** Lifestyle bonus matrix: [STR, DEX, CON]. */
    private const array LIFESTYLE_BONUSES = [
        'sedentary'   => [0, 0, 0],
        'moderate'    => [0, 1, 1],
        'active'      => [1, 1, 1],
        'very_active' => [1, 1, 2],
    ];

    /** Training frequency bonus matrix: [STR, DEX, CON]. */
    private const array FREQUENCY_BONUSES = [
        'none'     => [0, 0, 0],
        'light'    => [0, 0, 1],
        'moderate' => [1, 0, 1],
        'heavy'    => [1, 1, 1],
    ];

    /**
     * Calculate initial stats from onboarding answers.
     *
     * @return array{strength: int, dexterity: int, constitution: int} Stats totaling exactly 30
     */
    public function calculateInitialStats(
        WorkoutType $workoutType,
        Lifestyle $lifestyle,
        TrainingFrequency $frequency,
    ): array {
        // Start with base allocation of 5 per stat
        $strength = self::BASE_PER_STAT;
        $dexterity = self::BASE_PER_STAT;
        $constitution = self::BASE_PER_STAT;

        // Add workout type bonuses
        $workoutBonus = self::WORKOUT_BONUSES[$workoutType->value] ?? [0, 0, 0];
        $strength += $workoutBonus[0];
        $dexterity += $workoutBonus[1];
        $constitution += $workoutBonus[2];

        // Add lifestyle bonuses
        $lifestyleBonus = self::LIFESTYLE_BONUSES[$lifestyle->value] ?? [0, 0, 0];
        $strength += $lifestyleBonus[0];
        $dexterity += $lifestyleBonus[1];
        $constitution += $lifestyleBonus[2];

        // Add training frequency bonuses
        $frequencyBonus = self::FREQUENCY_BONUSES[$frequency->value] ?? [0, 0, 0];
        $strength += $frequencyBonus[0];
        $dexterity += $frequencyBonus[1];
        $constitution += $frequencyBonus[2];

        // Normalize to exactly TOTAL_POINTS
        return $this->normalize($strength, $dexterity, $constitution);
    }

    /**
     * Normalize three stat values to sum to exactly TOTAL_POINTS.
     *
     * If the sum exceeds the target, proportionally reduce each stat (round down)
     * and distribute any rounding remainder to the highest stat.
     * If the sum is below the target, add the deficit to the highest stat.
     *
     * @return array{strength: int, dexterity: int, constitution: int}
     */
    private function normalize(int $strength, int $dexterity, int $constitution): array
    {
        $sum = $strength + $dexterity + $constitution;

        if ($sum === self::TOTAL_POINTS) {
            return [
                'strength' => $strength,
                'dexterity' => $dexterity,
                'constitution' => $constitution,
            ];
        }

        if ($sum > self::TOTAL_POINTS) {
            // Proportionally reduce, rounding down
            $ratio = self::TOTAL_POINTS / $sum;
            $strength = (int) floor($strength * $ratio);
            $dexterity = (int) floor($dexterity * $ratio);
            $constitution = (int) floor($constitution * $ratio);

            // Distribute rounding remainder to the highest stat
            $remainder = self::TOTAL_POINTS - ($strength + $dexterity + $constitution);
            if ($remainder > 0) {
                // Find which stat is highest and add remainder there
                $stats = ['strength' => &$strength, 'dexterity' => &$dexterity, 'constitution' => &$constitution];
                arsort($stats);
                $highestKey = array_key_first($stats);
                $stats[$highestKey] += $remainder;
            }
        } else {
            // Sum < target: add deficit to the highest stat
            $deficit = self::TOTAL_POINTS - $sum;
            if ($strength >= $dexterity && $strength >= $constitution) {
                $strength += $deficit;
            } elseif ($dexterity >= $strength && $dexterity >= $constitution) {
                $dexterity += $deficit;
            } else {
                $constitution += $deficit;
            }
        }

        return [
            'strength' => $strength,
            'dexterity' => $dexterity,
            'constitution' => $constitution,
        ];
    }
}
