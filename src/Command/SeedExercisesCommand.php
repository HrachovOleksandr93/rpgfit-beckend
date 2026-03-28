<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Entity\SplitTemplate;
use App\Domain\Workout\Enum\Equipment;
use App\Domain\Workout\Enum\ExerciseDifficulty;
use App\Domain\Workout\Enum\ExerciseMovementType;
use App\Domain\Workout\Enum\MuscleGroup;
use App\Domain\Workout\Enum\SplitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to seed the exercise database and split templates.
 *
 * Populates ~120 exercises across 10 muscle groups and 6 split templates.
 * Idempotent by default: skips existing slugs. Use --clear to delete and re-seed.
 *
 * Usage: php bin/console app:seed-exercises [--clear]
 */
#[AsCommand(
    name: 'app:seed-exercises',
    description: 'Seed exercise database and split templates from research data',
)]
class SeedExercisesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Delete existing exercise and template data before seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $clear = $input->getOption('clear');

        if ($clear) {
            $io->warning('Clearing existing exercise and template data...');
            $this->clearExistingData();
            $io->info('Existing data cleared.');
        }

        // Seed exercises grouped by muscle
        $exerciseCount = $this->seedExercises($io);

        // Seed split templates
        $templateCount = $this->seedSplitTemplates($io);

        $this->entityManager->flush();

        $io->success(sprintf(
            'Seeding complete! Exercises: %d, Split templates: %d',
            $exerciseCount,
            $templateCount,
        ));

        return Command::SUCCESS;
    }

    /**
     * Delete all existing exercise and template data.
     * Order matters due to foreign key constraints.
     */
    private function clearExistingData(): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('DELETE FROM workout_plan_exercise_logs');
        $conn->executeStatement('DELETE FROM workout_plan_exercises');
        $conn->executeStatement('DELETE FROM workout_plans');
        $conn->executeStatement('DELETE FROM exercises');
        $conn->executeStatement('DELETE FROM split_templates');
    }

    /**
     * Seed all exercises from the research data.
     *
     * @return int Number of exercises created
     */
    private function seedExercises(SymfonyStyle $io): int
    {
        $exercises = $this->getExerciseDefinitions();
        $count = 0;

        foreach ($exercises as $data) {
            $existing = $this->entityManager->getRepository(Exercise::class)->findOneBy(['slug' => $data['slug']]);
            if ($existing) {
                $io->text(sprintf('  Skipping existing exercise: %s', $data['name']));
                continue;
            }

            $exercise = new Exercise();
            $exercise->setName($data['name'])
                ->setSlug($data['slug'])
                ->setPrimaryMuscle(MuscleGroup::from($data['primaryMuscle']))
                ->setSecondaryMuscles($data['secondaryMuscles'])
                ->setEquipment(Equipment::from($data['equipment']))
                ->setDifficulty(ExerciseDifficulty::from($data['difficulty']))
                ->setMovementType(ExerciseMovementType::from($data['movementType']))
                ->setPriority($data['priority'])
                ->setIsBaseExercise($data['isBase'] ?? false)
                ->setDefaultSets($data['defaultSets'] ?? 3)
                ->setDefaultRepsMin($data['defaultRepsMin'] ?? 8)
                ->setDefaultRepsMax($data['defaultRepsMax'] ?? 12)
                ->setDefaultRestSeconds($data['defaultRestSeconds'] ?? 90)
                ->setDescription($data['description'] ?? null);

            $this->entityManager->persist($exercise);
            $count++;
        }

        $io->text(sprintf('  Created %d exercises', $count));

        return $count;
    }

    /**
     * Seed split templates from the research data.
     *
     * @return int Number of templates created
     */
    private function seedSplitTemplates(SymfonyStyle $io): int
    {
        $templates = $this->getSplitTemplateDefinitions();
        $count = 0;

        foreach ($templates as $data) {
            $existing = $this->entityManager->getRepository(SplitTemplate::class)->findOneBy(['slug' => $data['slug']]);
            if ($existing) {
                $io->text(sprintf('  Skipping existing template: %s', $data['name']));
                continue;
            }

            $template = new SplitTemplate();
            $template->setName($data['name'])
                ->setSlug($data['slug'])
                ->setSplitType(SplitType::from($data['splitType']))
                ->setDaysPerWeek($data['daysPerWeek'])
                ->setDayConfigs($data['dayConfigs'])
                ->setDescription($data['description'] ?? null);

            $this->entityManager->persist($template);
            $count++;
        }

        $io->text(sprintf('  Created %d split templates', $count));

        return $count;
    }

    /**
     * Return all exercise definitions from the research data.
     *
     * Organized by muscle group: Chest (14), Back (14), Shoulders (12),
     * Biceps (10), Triceps (9), Quads (12), Hamstrings (10), Glutes (9),
     * Calves (6), Core (12) = 108 unique exercises.
     *
     * Some exercises that appear in multiple muscle groups are listed once
     * under their primary muscle to avoid duplicate slugs.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getExerciseDefinitions(): array
    {
        return [
            // === CHEST (14 exercises) ===
            ['name' => 'Barbell Bench Press', 'slug' => 'barbell-bench-press', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['triceps', 'shoulders'], 'equipment' => 'barbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'isBase' => true, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 120, 'description' => 'The king of chest exercises. Lie flat on a bench and press the barbell from chest to lockout.'],
            ['name' => 'Incline Barbell Press', 'slug' => 'incline-barbell-press', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['triceps', 'shoulders'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 120, 'description' => 'Targets the upper chest with an inclined bench angle of 30-45 degrees.'],
            ['name' => 'Dumbbell Bench Press', 'slug' => 'dumbbell-bench-press', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['triceps', 'shoulders'], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Greater range of motion than barbell, each arm works independently.'],
            ['name' => 'Incline Dumbbell Press', 'slug' => 'incline-dumbbell-press', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['triceps', 'shoulders'], 'equipment' => 'dumbbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Incline dumbbell pressing for upper chest development with unilateral control.'],
            ['name' => 'Decline Bench Press', 'slug' => 'decline-bench-press', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['triceps'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Emphasizes the lower portion of the chest with a declined bench angle.'],
            ['name' => 'Dumbbell Flyes', 'slug' => 'dumbbell-flyes', 'primaryMuscle' => 'chest', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Isolation movement for chest stretch and contraction with an arcing motion.'],
            ['name' => 'Cable Crossover', 'slug' => 'cable-crossover', 'primaryMuscle' => 'chest', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Constant tension chest isolation using cable pulleys from high to low.'],
            ['name' => 'Pec Deck Machine', 'slug' => 'pec-deck-machine', 'primaryMuscle' => 'chest', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Machine-guided chest isolation for controlled pectoral contraction.'],
            ['name' => 'Push-ups', 'slug' => 'push-ups', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['triceps', 'shoulders', 'core'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Fundamental bodyweight pushing movement targeting chest, shoulders, and triceps.'],
            ['name' => 'Chest Dips', 'slug' => 'chest-dips', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['triceps', 'shoulders'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Lean forward on parallel bars to target the lower chest with bodyweight.'],
            ['name' => 'Landmine Press', 'slug' => 'landmine-press', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['shoulders', 'triceps'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Angled pressing using a barbell anchored in a corner for unique chest activation.'],
            ['name' => 'Svend Press', 'slug' => 'svend-press', 'primaryMuscle' => 'chest', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Squeeze-focused pressing movement using plates for inner chest activation.'],
            ['name' => 'Machine Chest Press', 'slug' => 'machine-chest-press', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['triceps'], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Machine-guided horizontal pressing for safe, controlled chest training.'],
            ['name' => 'Close-Grip Bench Press', 'slug' => 'close-grip-bench-press', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['triceps'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Narrow grip bench press that shifts emphasis to triceps while still working chest.'],

            // === BACK (14 exercises) ===
            ['name' => 'Barbell Deadlift', 'slug' => 'barbell-deadlift', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['hamstrings', 'glutes', 'core'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'isBase' => true, 'defaultSets' => 4, 'defaultRepsMin' => 4, 'defaultRepsMax' => 8, 'defaultRestSeconds' => 180, 'description' => 'The ultimate posterior chain exercise. Lift the barbell from floor to hip lockout.'],
            ['name' => 'Barbell Row', 'slug' => 'barbell-row', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps'], 'equipment' => 'barbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'isBase' => true, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 120, 'description' => 'Hinge at the hips and row the barbell to your lower chest for thick back development.'],
            ['name' => 'Pull-ups', 'slug' => 'pull-ups', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 6, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 120, 'description' => 'Overhand grip vertical pulling. The gold standard for back width development.'],
            ['name' => 'Lat Pulldown', 'slug' => 'lat-pulldown', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps'], 'equipment' => 'cable', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Cable-based vertical pulling for lat development, great for all levels.'],
            ['name' => 'Seated Cable Row', 'slug' => 'seated-cable-row', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps'], 'equipment' => 'cable', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Seated horizontal rowing with cable for controlled back thickness work.'],
            ['name' => 'T-Bar Row', 'slug' => 't-bar-row', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Chest-supported or bent-over T-bar rowing for mid-back thickness.'],
            ['name' => 'Dumbbell Row', 'slug' => 'dumbbell-row', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps'], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Single-arm rowing on a bench for unilateral back development.'],
            ['name' => 'Face Pulls', 'slug' => 'face-pulls', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders'], 'equipment' => 'cable', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Cable pull to face height for rear delt and upper back health.'],
            ['name' => 'Straight-Arm Pulldown', 'slug' => 'straight-arm-pulldown', 'primaryMuscle' => 'back', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Lat isolation with straight arms, pulling a cable bar from high to thighs.'],
            ['name' => 'Hyperextensions', 'slug' => 'hyperextensions', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['glutes', 'hamstrings'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Lower back extension on a Roman chair for spinal erector strengthening.'],
            ['name' => 'Chin-ups', 'slug' => 'chin-ups', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 6, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 120, 'description' => 'Underhand grip vertical pull that emphasizes biceps alongside back.'],
            ['name' => 'Pendlay Row', 'slug' => 'pendlay-row', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps', 'core'], 'equipment' => 'barbell', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 4, 'defaultRepsMin' => 5, 'defaultRepsMax' => 8, 'defaultRestSeconds' => 120, 'description' => 'Strict barbell row from the floor with a dead stop between reps.'],
            ['name' => 'Cable Pullover', 'slug' => 'cable-pullover', 'primaryMuscle' => 'back', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Cable-based pullover for lat isolation with constant tension.'],
            ['name' => 'Machine Row', 'slug' => 'machine-row', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps'], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Machine-guided horizontal rowing for safe, controlled back work.'],

            // === SHOULDERS (12 exercises, Face Pulls shared with back) ===
            ['name' => 'Overhead Press', 'slug' => 'overhead-press', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['triceps', 'core'], 'equipment' => 'barbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'isBase' => true, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 120, 'description' => 'Standing barbell press overhead for total shoulder mass and strength.'],
            ['name' => 'Dumbbell Shoulder Press', 'slug' => 'dumbbell-shoulder-press', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['triceps'], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Seated or standing dumbbell pressing for shoulder development.'],
            ['name' => 'Arnold Press', 'slug' => 'arnold-press', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['triceps'], 'equipment' => 'dumbbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Rotational pressing movement hitting all three delt heads.'],
            ['name' => 'Lateral Raises', 'slug' => 'lateral-raises', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Raise dumbbells to the sides for medial deltoid width.'],
            ['name' => 'Front Raises', 'slug' => 'front-raises', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Raise dumbbells in front to target the anterior deltoid.'],
            ['name' => 'Rear Delt Flyes', 'slug' => 'rear-delt-flyes', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Bent-over dumbbell reverse flyes for posterior deltoid development.'],
            ['name' => 'Cable Lateral Raise', 'slug' => 'cable-lateral-raise', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Cable lateral raises for constant tension on the medial deltoid.'],
            ['name' => 'Upright Row', 'slug' => 'upright-row', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['biceps'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Pull the barbell up along the body to chin height for traps and delts.'],
            ['name' => 'Shrugs', 'slug' => 'shrugs', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Elevate the shoulders holding dumbbells for upper trapezius development.'],
            ['name' => 'Machine Shoulder Press', 'slug' => 'machine-shoulder-press', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['triceps'], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Machine-guided overhead pressing for controlled shoulder training.'],
            ['name' => 'Reverse Pec Deck', 'slug' => 'reverse-pec-deck', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Machine reverse flyes for rear delt isolation.'],
            ['name' => 'Pike Push-ups', 'slug' => 'pike-push-ups', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['triceps', 'core'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Pike position push-ups to simulate overhead pressing with bodyweight.'],

            // === BICEPS (10 exercises) ===
            ['name' => 'Barbell Curl', 'slug' => 'barbell-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'barbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'The classic bicep builder. Curl a straight barbell from thighs to shoulders.'],
            ['name' => 'Dumbbell Curl', 'slug' => 'dumbbell-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Alternating or simultaneous dumbbell curls for bicep development.'],
            ['name' => 'Hammer Curl', 'slug' => 'hammer-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Neutral grip curls targeting the brachialis and brachioradialis.'],
            ['name' => 'Preacher Curl', 'slug' => 'preacher-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Curl on a preacher bench to isolate the biceps and eliminate momentum.'],
            ['name' => 'Concentration Curl', 'slug' => 'concentration-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Seated single-arm curl braced against the inner thigh for peak contraction.'],
            ['name' => 'Cable Curl', 'slug' => 'cable-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Cable curls for constant tension throughout the entire range of motion.'],
            ['name' => 'Incline Dumbbell Curl', 'slug' => 'incline-dumbbell-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Incline bench curls for a stretched bicep position and long head emphasis.'],
            ['name' => 'EZ-Bar Curl', 'slug' => 'ez-bar-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'barbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'EZ-bar curls for wrist-friendly bicep training.'],
            ['name' => 'Spider Curl', 'slug' => 'spider-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'advanced', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Prone incline bench curls for maximal bicep short head contraction.'],
            ['name' => 'Reverse Curl', 'slug' => 'reverse-curl', 'primaryMuscle' => 'biceps', 'secondaryMuscles' => [], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Overhand grip curls targeting the brachioradialis and forearms.'],

            // === TRICEPS (9 exercises, Close-Grip Bench in chest) ===
            ['name' => 'Skull Crushers', 'slug' => 'skull-crushers', 'primaryMuscle' => 'triceps', 'secondaryMuscles' => [], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Lying tricep extension lowering the bar to the forehead for long head emphasis.'],
            ['name' => 'Tricep Pushdown', 'slug' => 'tricep-pushdown', 'primaryMuscle' => 'triceps', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Cable pushdown with a straight or V-bar for tricep lateral head.'],
            ['name' => 'Overhead Tricep Extension', 'slug' => 'overhead-tricep-extension', 'primaryMuscle' => 'triceps', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Overhead dumbbell extension for tricep long head development.'],
            ['name' => 'Dips', 'slug' => 'dips', 'primaryMuscle' => 'triceps', 'secondaryMuscles' => ['chest', 'shoulders'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Upright body dips on parallel bars emphasizing triceps over chest.'],
            ['name' => 'French Press', 'slug' => 'french-press', 'primaryMuscle' => 'triceps', 'secondaryMuscles' => [], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Seated or standing barbell overhead extension for tricep mass.'],
            ['name' => 'Cable Overhead Extension', 'slug' => 'cable-overhead-extension', 'primaryMuscle' => 'triceps', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Cable overhead extension for constant-tension tricep long head work.'],
            ['name' => 'Kickbacks', 'slug' => 'kickbacks', 'primaryMuscle' => 'triceps', 'secondaryMuscles' => [], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Bent-over dumbbell kickbacks for tricep peak contraction.'],
            ['name' => 'Diamond Push-ups', 'slug' => 'diamond-push-ups', 'primaryMuscle' => 'triceps', 'secondaryMuscles' => ['chest'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Close-hand push-ups with diamond hand position targeting triceps.'],
            ['name' => 'Rope Pushdown', 'slug' => 'rope-pushdown', 'primaryMuscle' => 'triceps', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Rope attachment pushdown allowing wrist rotation at the bottom.'],

            // === QUADS (12 exercises) ===
            ['name' => 'Barbell Back Squat', 'slug' => 'barbell-back-squat', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'core'], 'equipment' => 'barbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'isBase' => true, 'defaultSets' => 4, 'defaultRepsMin' => 5, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 180, 'description' => 'The king of leg exercises. Squat with a barbell on the upper back.'],
            ['name' => 'Front Squat', 'slug' => 'front-squat', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['core', 'glutes'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 120, 'description' => 'Barbell on the front shoulders forces upright torso and quad dominance.'],
            ['name' => 'Leg Press', 'slug' => 'leg-press', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes'], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 90, 'description' => 'Machine pressing for heavy quad loading without spinal compression.'],
            ['name' => 'Lunges', 'slug' => 'lunges', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'hamstrings'], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Step forward and lower into a lunge for unilateral leg development.'],
            ['name' => 'Bulgarian Split Squat', 'slug' => 'bulgarian-split-squat', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes'], 'equipment' => 'dumbbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Rear foot elevated split squat for deep quad stretch and single-leg strength.'],
            ['name' => 'Leg Extension', 'slug' => 'leg-extension', 'primaryMuscle' => 'quads', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Machine isolation for the quadriceps with controlled knee extension.'],
            ['name' => 'Hack Squat', 'slug' => 'hack-squat', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes'], 'equipment' => 'machine', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Machine squat variant with back support for quad-focused loading.'],
            ['name' => 'Goblet Squat', 'slug' => 'goblet-squat', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'core'], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Hold a dumbbell at chest height for a beginner-friendly squat pattern.'],
            ['name' => 'Step-ups', 'slug' => 'step-ups', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes'], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Step onto an elevated surface for single-leg quad and glute training.'],
            ['name' => 'Sissy Squat', 'slug' => 'sissy-squat', 'primaryMuscle' => 'quads', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'advanced', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Lean back while squatting for extreme quad isolation at the knee.'],
            ['name' => 'Wall Sit', 'slug' => 'wall-sit', 'primaryMuscle' => 'quads', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 60, 'description' => 'Static hold against a wall with thighs parallel to the floor.'],
            ['name' => 'Walking Lunges', 'slug' => 'walking-lunges', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes'], 'equipment' => 'dumbbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 16, 'defaultRestSeconds' => 60, 'description' => 'Continuous forward lunges for quad endurance and functional leg strength.'],

            // === HAMSTRINGS (10 exercises) ===
            ['name' => 'Romanian Deadlift', 'slug' => 'romanian-deadlift', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => ['glutes', 'back'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'isBase' => true, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 120, 'description' => 'Hip hinge with barbell for hamstring and glute development. Keep legs nearly straight.'],
            ['name' => 'Leg Curl', 'slug' => 'leg-curl', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Lying or prone machine curl for direct hamstring isolation.'],
            ['name' => 'Stiff-Leg Deadlift', 'slug' => 'stiff-leg-deadlift', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => ['glutes', 'back'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Deadlift with minimal knee bend for maximal hamstring stretch.'],
            ['name' => 'Good Mornings', 'slug' => 'good-mornings', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => ['back', 'glutes'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Barbell on back, hinge at hips for posterior chain strengthening.'],
            ['name' => 'Nordic Curl', 'slug' => 'nordic-curl', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'advanced', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 4, 'defaultRepsMax' => 8, 'defaultRestSeconds' => 90, 'description' => 'Eccentric hamstring curl from kneeling position for injury prevention.'],
            ['name' => 'Seated Leg Curl', 'slug' => 'seated-leg-curl', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Seated machine curl for hamstring isolation in a hip-flexed position.'],
            ['name' => 'Single-Leg Romanian Deadlift', 'slug' => 'single-leg-romanian-deadlift', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => ['glutes'], 'equipment' => 'dumbbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'description' => 'Unilateral hip hinge for hamstring and balance development.'],
            ['name' => 'Glute-Ham Raise', 'slug' => 'glute-ham-raise', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => ['glutes'], 'equipment' => 'bodyweight', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 90, 'description' => 'GHD machine exercise combining hip extension and knee flexion.'],
            ['name' => 'Cable Pull-Through', 'slug' => 'cable-pull-through', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => ['glutes'], 'equipment' => 'cable', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Cable between legs, hinge and pull through for posterior chain activation.'],
            ['name' => 'Kettlebell Swing', 'slug' => 'kettlebell-swing', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => ['glutes', 'core'], 'equipment' => 'kettlebell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Explosive hip hinge swinging a kettlebell for power and conditioning.'],

            // === GLUTES (9 exercises, Step-ups shared with quads) ===
            ['name' => 'Hip Thrust', 'slug' => 'hip-thrust', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['hamstrings'], 'equipment' => 'barbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'isBase' => true, 'defaultSets' => 4, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 90, 'description' => 'Barbell loaded hip extension from a bench for maximal glute activation.'],
            ['name' => 'Barbell Glute Bridge', 'slug' => 'barbell-glute-bridge', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['hamstrings'], 'equipment' => 'barbell', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Floor-based barbell hip extension for glute development.'],
            ['name' => 'Cable Kickback', 'slug' => 'cable-kickback', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Cable hip extension kicking backward for glute isolation.'],
            ['name' => 'Sumo Deadlift', 'slug' => 'sumo-deadlift', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['quads', 'back'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 5, 'defaultRepsMax' => 8, 'defaultRestSeconds' => 180, 'description' => 'Wide-stance deadlift that emphasizes glutes and inner thigh.'],
            ['name' => 'Fire Hydrants', 'slug' => 'fire-hydrants', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'description' => 'Quadruped hip abduction for glute medius activation.'],
            ['name' => 'Clamshells', 'slug' => 'clamshells', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => [], 'equipment' => 'resistance_band', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 15, 'defaultRepsMax' => 25, 'defaultRestSeconds' => 30, 'description' => 'Side-lying hip rotation with band for glute medius activation.'],
            ['name' => 'Hip Abduction Machine', 'slug' => 'hip-abduction-machine', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Machine hip abduction for outer glute and hip strengthening.'],
            ['name' => 'Frog Pumps', 'slug' => 'frog-pumps', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 15, 'defaultRepsMax' => 25, 'defaultRestSeconds' => 30, 'description' => 'Glute bridge with soles together for maximal glute squeeze.'],
            ['name' => 'Donkey Kicks', 'slug' => 'donkey-kicks', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'description' => 'Quadruped hip extension kicking the foot toward the ceiling.'],

            // === CALVES (6 exercises) ===
            ['name' => 'Standing Calf Raise', 'slug' => 'standing-calf-raise', 'primaryMuscle' => 'calves', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Machine standing calf raise for gastrocnemius development.'],
            ['name' => 'Seated Calf Raise', 'slug' => 'seated-calf-raise', 'primaryMuscle' => 'calves', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 4, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Seated calf raises targeting the soleus muscle with bent knees.'],
            ['name' => 'Donkey Calf Raise', 'slug' => 'donkey-calf-raise', 'primaryMuscle' => 'calves', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Bent-over calf raise for deep stretch and full range of motion.'],
            ['name' => 'Single-Leg Calf Raise', 'slug' => 'single-leg-calf-raise', 'primaryMuscle' => 'calves', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'description' => 'Single-leg bodyweight calf raise on a step for unilateral training.'],
            ['name' => 'Smith Machine Calf Raise', 'slug' => 'smith-machine-calf-raise', 'primaryMuscle' => 'calves', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 4, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Smith machine standing calf raise for guided heavy loading.'],
            ['name' => 'Calf Press on Leg Press', 'slug' => 'calf-press-on-leg-press', 'primaryMuscle' => 'calves', 'secondaryMuscles' => [], 'equipment' => 'machine', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'description' => 'Use the leg press machine for calf raises with adjustable resistance.'],

            // === CORE (12 exercises) ===
            ['name' => 'Plank', 'slug' => 'plank', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 60, 'description' => 'Isometric hold in a push-up position for total core stability.'],
            ['name' => 'Crunches', 'slug' => 'crunches', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 15, 'defaultRepsMax' => 25, 'defaultRestSeconds' => 30, 'description' => 'Basic abdominal crunch lifting shoulders off the floor.'],
            ['name' => 'Hanging Leg Raise', 'slug' => 'hanging-leg-raise', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Hang from a bar and raise legs for lower abdominal activation.'],
            ['name' => 'Russian Twist', 'slug' => 'russian-twist', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 15, 'defaultRepsMax' => 25, 'defaultRestSeconds' => 30, 'description' => 'Seated trunk rotation for oblique development.'],
            ['name' => 'Ab Wheel Rollout', 'slug' => 'ab-wheel-rollout', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Roll an ab wheel forward from kneeling for anti-extension core work.'],
            ['name' => 'Cable Woodchop', 'slug' => 'cable-woodchop', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Diagonal cable rotation mimicking a chopping motion for rotational core strength.'],
            ['name' => 'Mountain Climbers', 'slug' => 'mountain-climbers', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 20, 'defaultRepsMax' => 40, 'defaultRestSeconds' => 30, 'description' => 'Dynamic plank alternating knee drives for core and cardio conditioning.'],
            ['name' => 'Dead Bug', 'slug' => 'dead-bug', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 30, 'description' => 'Supine anti-extension exercise alternating opposite arm and leg reach.'],
            ['name' => 'Bicycle Crunch', 'slug' => 'bicycle-crunch', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 15, 'defaultRepsMax' => 25, 'defaultRestSeconds' => 30, 'description' => 'Alternating elbow-to-knee crunch for oblique and rectus abdominis.'],
            ['name' => 'Pallof Press', 'slug' => 'pallof-press', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'cable', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'description' => 'Anti-rotation cable press for deep core stability.'],
            ['name' => 'Leg Raise', 'slug' => 'leg-raise', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'description' => 'Lying leg raise for lower abdominal development.'],
            ['name' => 'Side Plank', 'slug' => 'side-plank', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 20, 'defaultRepsMax' => 45, 'defaultRestSeconds' => 30, 'description' => 'Lateral isometric hold for oblique and hip stability.'],
        ];
    }

    /**
     * Return all split template definitions.
     *
     * 6 templates covering 2-6 training days per week with proper muscle
     * group assignments following synergy rules (push/pull/legs).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getSplitTemplateDefinitions(): array
    {
        return [
            [
                'name' => 'Full Body A',
                'slug' => 'full-body-a',
                'splitType' => 'full_body',
                'daysPerWeek' => 2,
                'description' => 'Two-day full body program focusing on compound movements. Ideal for beginners or time-limited schedules.',
                'dayConfigs' => [
                    ['day' => 1, 'name' => 'Full Body A', 'muscleGroups' => ['chest', 'back', 'shoulders', 'quads', 'hamstrings', 'core']],
                    ['day' => 2, 'name' => 'Full Body B', 'muscleGroups' => ['chest', 'back', 'shoulders', 'glutes', 'calves', 'biceps', 'triceps']],
                ],
            ],
            [
                'name' => 'Full Body B',
                'slug' => 'full-body-b',
                'splitType' => 'full_body',
                'daysPerWeek' => 2,
                'description' => 'Alternative two-day full body program with different exercise selection. Rotate with Full Body A.',
                'dayConfigs' => [
                    ['day' => 1, 'name' => 'Full Body A', 'muscleGroups' => ['back', 'chest', 'quads', 'shoulders', 'core', 'biceps']],
                    ['day' => 2, 'name' => 'Full Body B', 'muscleGroups' => ['back', 'chest', 'hamstrings', 'glutes', 'calves', 'triceps']],
                ],
            ],
            [
                'name' => 'Push/Pull/Legs',
                'slug' => 'push-pull-legs',
                'splitType' => 'push_pull_legs',
                'daysPerWeek' => 3,
                'description' => 'Classic three-day split grouping muscles by pushing, pulling, and leg movements.',
                'dayConfigs' => [
                    ['day' => 1, 'name' => 'Push', 'muscleGroups' => ['chest', 'shoulders', 'triceps']],
                    ['day' => 2, 'name' => 'Pull', 'muscleGroups' => ['back', 'biceps']],
                    ['day' => 3, 'name' => 'Legs', 'muscleGroups' => ['quads', 'hamstrings', 'glutes', 'calves']],
                ],
            ],
            [
                'name' => 'Upper/Lower A',
                'slug' => 'upper-lower-a',
                'splitType' => 'upper_lower',
                'daysPerWeek' => 4,
                'description' => 'Four-day upper/lower split. Each muscle group is trained twice per week.',
                'dayConfigs' => [
                    ['day' => 1, 'name' => 'Upper A', 'muscleGroups' => ['chest', 'back', 'shoulders', 'biceps', 'triceps']],
                    ['day' => 2, 'name' => 'Lower A', 'muscleGroups' => ['quads', 'hamstrings', 'glutes', 'calves', 'core']],
                    ['day' => 3, 'name' => 'Upper B', 'muscleGroups' => ['chest', 'back', 'shoulders', 'biceps', 'triceps']],
                    ['day' => 4, 'name' => 'Lower B', 'muscleGroups' => ['quads', 'hamstrings', 'glutes', 'calves', 'core']],
                ],
            ],
            [
                'name' => 'PPL + Upper/Lower',
                'slug' => 'ppl-upper-lower',
                'splitType' => 'push_pull_legs',
                'daysPerWeek' => 5,
                'description' => 'Five-day hybrid combining PPL with upper/lower for advanced frequency.',
                'dayConfigs' => [
                    ['day' => 1, 'name' => 'Push', 'muscleGroups' => ['chest', 'shoulders', 'triceps']],
                    ['day' => 2, 'name' => 'Pull', 'muscleGroups' => ['back', 'biceps']],
                    ['day' => 3, 'name' => 'Legs', 'muscleGroups' => ['quads', 'hamstrings', 'glutes', 'calves']],
                    ['day' => 4, 'name' => 'Upper', 'muscleGroups' => ['chest', 'back', 'shoulders', 'biceps', 'triceps']],
                    ['day' => 5, 'name' => 'Lower', 'muscleGroups' => ['quads', 'hamstrings', 'glutes', 'calves', 'core']],
                ],
            ],
            [
                'name' => 'PPL x2',
                'slug' => 'ppl-x2',
                'splitType' => 'push_pull_legs',
                'daysPerWeek' => 6,
                'description' => 'Six-day push/pull/legs run twice per week. Maximum frequency for advanced lifters.',
                'dayConfigs' => [
                    ['day' => 1, 'name' => 'Push A', 'muscleGroups' => ['chest', 'shoulders', 'triceps']],
                    ['day' => 2, 'name' => 'Pull A', 'muscleGroups' => ['back', 'biceps']],
                    ['day' => 3, 'name' => 'Legs A', 'muscleGroups' => ['quads', 'hamstrings', 'glutes', 'calves']],
                    ['day' => 4, 'name' => 'Push B', 'muscleGroups' => ['chest', 'shoulders', 'triceps']],
                    ['day' => 5, 'name' => 'Pull B', 'muscleGroups' => ['back', 'biceps']],
                    ['day' => 6, 'name' => 'Legs B', 'muscleGroups' => ['quads', 'hamstrings', 'glutes', 'calves', 'core']],
                ],
            ],
        ];
    }
}
