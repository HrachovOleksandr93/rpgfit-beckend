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
 * Seed game_settings with battle calibration parameters.
 *
 * Idempotent: skips settings that already exist. Creates all required
 * battle_* and workout_volume_* settings for the BattleResultCalculator.
 *
 * Usage: php bin/console app:seed-battle-settings
 */
#[AsCommand(
    name: 'app:seed-battle-settings',
    description: 'Seed game_settings table with battle calibration parameters',
)]
class SeedBattleSettingsCommand extends Command
{
    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = 0;

        $settings = $this->getSettingDefinitions();

        foreach ($settings as $data) {
            $existing = $this->gameSettingRepository->findByKey($data['key']);
            if ($existing !== null) {
                $io->text(sprintf('  Skipping existing setting: %s', $data['key']));
                continue;
            }

            $setting = new GameSetting();
            $setting->setCategory($data['category']);
            $setting->setKey($data['key']);
            $setting->setValue($data['value']);
            $setting->setDescription($data['description']);

            $this->entityManager->persist($setting);
            $count++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Seeded %d battle settings (skipped %d existing).', $count, count($settings) - $count));

        return Command::SUCCESS;
    }

    /**
     * Define all battle calibration settings.
     *
     * @return array<int, array{category: string, key: string, value: string, description: string}>
     */
    private function getSettingDefinitions(): array
    {
        return [
            [
                'category' => 'battle',
                'key' => 'battle_tick_frequency',
                'value' => '6',
                'description' => 'Number of damage ticks per minute (every 10 seconds = 6 per minute).',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_base_damage_multiplier',
                'value' => '1.0',
                'description' => 'Global base damage scaling multiplier.',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_strength_damage_factor',
                'value' => '0.8',
                'description' => 'How much STR contributes to damage for strength activities.',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_dex_damage_factor',
                'value' => '0.8',
                'description' => 'How much DEX contributes to damage for DEX activities.',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_con_damage_factor',
                'value' => '0.7',
                'description' => 'How much CON contributes to damage for CON activities.',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_random_variance',
                'value' => '0.15',
                'description' => 'Plus/minus damage variance per tick (0.15 = 15%).',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_overperform_bonus',
                'value' => '0.10',
                'description' => 'Base bonus XP fraction for completing 100%+ of plan (10%).',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_overperform_per_mob',
                'value' => '0.05',
                'description' => 'Additional bonus XP fraction per extra mob beyond plan (normal mode).',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_raid_overperform_per_mob',
                'value' => '0.10',
                'description' => 'Additional bonus XP fraction per extra mob beyond plan (raid mode).',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_fail_threshold',
                'value' => '0.50',
                'description' => 'Completion fraction below which the session is considered failed.',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_partial_threshold',
                'value' => '0.75',
                'description' => 'Completion fraction below which the session is survived (50-75%).',
            ],
            [
                'category' => 'battle',
                'key' => 'battle_success_threshold',
                'value' => '1.00',
                'description' => 'Completion fraction at or above which the session is fully completed.',
            ],
            [
                'category' => 'battle',
                'key' => 'workout_volume_anomaly_max_weight',
                'value' => '300',
                'description' => 'Maximum reasonable weight in kg per set (anomaly filter).',
            ],
            [
                'category' => 'battle',
                'key' => 'workout_volume_anomaly_max_reps',
                'value' => '100',
                'description' => 'Maximum reasonable reps per set (anomaly filter).',
            ],
        ];
    }
}
