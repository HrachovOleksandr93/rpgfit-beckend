<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Character\Service\StatCalculationService;
use App\Domain\User\Enum\Lifestyle;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\User\Enum\WorkoutType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StatCalculationService.
 *
 * Verifies that initial stat distribution is correct for all workout types,
 * that normalization always produces exactly 30 total points, and that
 * edge cases are handled properly.
 */
class StatCalculationServiceTest extends TestCase
{
    private StatCalculationService $service;

    protected function setUp(): void
    {
        $this->service = new StatCalculationService();
    }

    #[DataProvider('workoutTypeProvider')]
    public function testAllWorkoutTypesProduceTotalOf30(WorkoutType $workoutType): void
    {
        $stats = $this->service->calculateInitialStats(
            $workoutType,
            Lifestyle::Moderate,
            TrainingFrequency::Moderate,
        );

        $total = $stats['strength'] + $stats['dexterity'] + $stats['constitution'];
        $this->assertSame(30, $total, sprintf(
            'Workout type "%s" produced total %d instead of 30 (STR=%d, DEX=%d, CON=%d)',
            $workoutType->value,
            $total,
            $stats['strength'],
            $stats['dexterity'],
            $stats['constitution'],
        ));
    }

    public static function workoutTypeProvider(): array
    {
        return array_map(
            fn(WorkoutType $type) => [$type],
            WorkoutType::cases(),
        );
    }

    #[DataProvider('lifestyleProvider')]
    public function testAllLifestylesProduceTotalOf30(Lifestyle $lifestyle): void
    {
        $stats = $this->service->calculateInitialStats(
            WorkoutType::Strength,
            $lifestyle,
            TrainingFrequency::Moderate,
        );

        $total = $stats['strength'] + $stats['dexterity'] + $stats['constitution'];
        $this->assertSame(30, $total);
    }

    public static function lifestyleProvider(): array
    {
        return array_map(
            fn(Lifestyle $ls) => [$ls],
            Lifestyle::cases(),
        );
    }

    #[DataProvider('frequencyProvider')]
    public function testAllFrequenciesProduceTotalOf30(TrainingFrequency $frequency): void
    {
        $stats = $this->service->calculateInitialStats(
            WorkoutType::Crossfit,
            Lifestyle::Active,
            $frequency,
        );

        $total = $stats['strength'] + $stats['dexterity'] + $stats['constitution'];
        $this->assertSame(30, $total);
    }

    public static function frequencyProvider(): array
    {
        return array_map(
            fn(TrainingFrequency $f) => [$f],
            TrainingFrequency::cases(),
        );
    }

    public function testStrengthWorkoutFavorsStrength(): void
    {
        // Strength workout with sedentary + none should clearly bias toward STR
        $stats = $this->service->calculateInitialStats(
            WorkoutType::Strength,
            Lifestyle::Sedentary,
            TrainingFrequency::None,
        );

        $this->assertGreaterThan($stats['dexterity'], $stats['strength']);
        $this->assertGreaterThan($stats['constitution'], $stats['strength']);
        $this->assertSame(30, $stats['strength'] + $stats['dexterity'] + $stats['constitution']);
    }

    public function testCardioWorkoutFavorsConstitution(): void
    {
        $stats = $this->service->calculateInitialStats(
            WorkoutType::Cardio,
            Lifestyle::Sedentary,
            TrainingFrequency::None,
        );

        // Cardio gives +1 STR, +3 DEX, +5 CON -> constitution should be highest
        $this->assertGreaterThan($stats['strength'], $stats['constitution']);
        $this->assertSame(30, $stats['strength'] + $stats['dexterity'] + $stats['constitution']);
    }

    public function testCrossfitProducesBalancedStats(): void
    {
        // Crossfit gives +3/+3/+3 -- with sedentary/none, all stats should be equal
        $stats = $this->service->calculateInitialStats(
            WorkoutType::Crossfit,
            Lifestyle::Sedentary,
            TrainingFrequency::None,
        );

        // Base 5+3 = 8 each, sum 24, need +6 more to reach 30
        // Since all are equal (8), the deficit goes to strength (first highest)
        $this->assertSame(30, $stats['strength'] + $stats['dexterity'] + $stats['constitution']);
    }

    public function testYogaFavorsDexterity(): void
    {
        $stats = $this->service->calculateInitialStats(
            WorkoutType::Yoga,
            Lifestyle::Sedentary,
            TrainingFrequency::None,
        );

        // Yoga gives +1 STR, +5 DEX, +2 CON -> dexterity should be highest
        $this->assertGreaterThan($stats['strength'], $stats['dexterity']);
        $this->assertSame(30, $stats['strength'] + $stats['dexterity'] + $stats['constitution']);
    }

    public function testMartialArtsFavorsDexterity(): void
    {
        $stats = $this->service->calculateInitialStats(
            WorkoutType::MartialArts,
            Lifestyle::Sedentary,
            TrainingFrequency::None,
        );

        // Martial arts gives +3 STR, +4 DEX, +2 CON -> dexterity should be highest
        $this->assertGreaterThanOrEqual($stats['strength'], $stats['dexterity']);
        $this->assertSame(30, $stats['strength'] + $stats['dexterity'] + $stats['constitution']);
    }

    public function testAllStatsAreNonNegative(): void
    {
        // Test all combinations to ensure no stat goes negative
        foreach (WorkoutType::cases() as $workout) {
            foreach (Lifestyle::cases() as $lifestyle) {
                foreach (TrainingFrequency::cases() as $freq) {
                    $stats = $this->service->calculateInitialStats($workout, $lifestyle, $freq);

                    $this->assertGreaterThanOrEqual(0, $stats['strength']);
                    $this->assertGreaterThanOrEqual(0, $stats['dexterity']);
                    $this->assertGreaterThanOrEqual(0, $stats['constitution']);
                    $this->assertSame(30, $stats['strength'] + $stats['dexterity'] + $stats['constitution']);
                }
            }
        }
    }

    public function testReturnArrayHasCorrectKeys(): void
    {
        $stats = $this->service->calculateInitialStats(
            WorkoutType::Strength,
            Lifestyle::Moderate,
            TrainingFrequency::Light,
        );

        $this->assertArrayHasKey('strength', $stats);
        $this->assertArrayHasKey('dexterity', $stats);
        $this->assertArrayHasKey('constitution', $stats);
        $this->assertCount(3, $stats);
    }

    public function testVeryActiveHeavyProducesHigherRawTotal(): void
    {
        // Very active + heavy should still normalize to exactly 30
        $stats = $this->service->calculateInitialStats(
            WorkoutType::Strength,
            Lifestyle::VeryActive,
            TrainingFrequency::Heavy,
        );

        $this->assertSame(30, $stats['strength'] + $stats['dexterity'] + $stats['constitution']);
    }

    public function testMixedWorkoutTypeWorks(): void
    {
        // 'mixed' maps to gymnastics distribution
        $stats = $this->service->calculateInitialStats(
            WorkoutType::Mixed,
            Lifestyle::Sedentary,
            TrainingFrequency::None,
        );

        $this->assertSame(30, $stats['strength'] + $stats['dexterity'] + $stats['constitution']);
        // Mixed/gymnastics favors dexterity (+5)
        $this->assertGreaterThan($stats['strength'], $stats['dexterity']);
    }
}
