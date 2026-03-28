<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Config\Entity\GameSetting;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to seed workout-related game settings.
 *
 * Creates configurable parameters for the workout plan generator:
 * - Exercises per session
 * - Cardio progression rate
 * - Reward tier multipliers (bronze/silver/gold)
 * - Base XP per workout
 *
 * Idempotent: skips settings that already exist.
 *
 * Usage: php bin/console app:seed-workout-settings
 */
#[AsCommand(
    name: 'app:seed-workout-settings',
    description: 'Seed workout-related game settings for the plan generator',
)]
class SeedWorkoutSettingsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $settings = [
            [
                'category' => 'workout',
                'key' => 'workout_exercises_per_session',
                'value' => '7',
                'description' => 'Number of exercises per generated workout session (6-8 typical)',
            ],
            [
                'category' => 'workout',
                'key' => 'workout_cardio_progression_rate',
                'value' => '0.1',
                'description' => 'Weekly progression rate for cardio activities (0.1 = 10% increase)',
            ],
            [
                'category' => 'workout',
                'key' => 'workout_reward_bronze_multiplier',
                'value' => '0.6',
                'description' => 'Bronze tier threshold multiplier (60% of target value)',
            ],
            [
                'category' => 'workout',
                'key' => 'workout_reward_silver_multiplier',
                'value' => '1.0',
                'description' => 'Silver tier threshold multiplier (100% of target value)',
            ],
            [
                'category' => 'workout',
                'key' => 'workout_reward_gold_multiplier',
                'value' => '1.3',
                'description' => 'Gold tier threshold multiplier (130% of target value)',
            ],
            [
                'category' => 'workout',
                'key' => 'workout_base_xp_per_workout',
                'value' => '100',
                'description' => 'Base XP awarded for completing a workout (before tier multiplier)',
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($settings as $settingData) {
            $existing = $this->gameSettingRepository->findByKey($settingData['key']);

            if ($existing !== null) {
                $skipped++;
                $io->text(sprintf('  Skipped (exists): %s', $settingData['key']));
                continue;
            }

            $setting = new GameSetting();
            $setting->setCategory($settingData['category']);
            $setting->setKey($settingData['key']);
            $setting->setValue($settingData['value']);
            $setting->setDescription($settingData['description']);

            $this->entityManager->persist($setting);
            $created++;
            $io->text(sprintf('  Created: %s = %s', $settingData['key'], $settingData['value']));
        }

        $this->entityManager->flush();

        $io->success(sprintf('Workout settings seeded: %d created, %d skipped.', $created, $skipped));

        return Command::SUCCESS;
    }
}
