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
 * Populates ~220 exercises across 10 muscle groups, 15 activity categories, and 6 split templates.
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
                ->setDescription($data['description'] ?? null)
                ->setActivityCategory($data['activityCategory'] ?? null);

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
     * Organized by muscle group for gym: Chest (14), Back (14), Shoulders (12),
     * Biceps (10), Triceps (9), Quads (12), Hamstrings (10), Glutes (9),
     * Calves (6), Core (12) = 108 gym exercises.
     *
     * Plus activity-based exercises: Combat (10), Running (8), Walking (6),
     * Cycling (8), Swimming (8), Flexibility (12), Cardio (10), Dance (6),
     * Winter Sports (6), Racquet Sports (6), Team Sports (6), Water Sports (6),
     * Outdoor (6), Mind & Body (6), Other (4) = 108 activity exercises.
     *
     * Total: 216 exercises.
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

            // ===================================================================
            // ACTIVITY-BASED EXERCISES (non-gym categories)
            // ===================================================================

            // === COMBAT (10 exercises) ===
            ['name' => 'Shadow Boxing', 'slug' => 'shadow-boxing', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core', 'biceps'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 3, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 60, 'activityCategory' => 'combat', 'description' => 'Throw punches at the air to build boxing technique, coordination, and shoulder endurance.'],
            ['name' => 'Heavy Bag Rounds', 'slug' => 'heavy-bag-rounds', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core', 'chest', 'biceps'], 'equipment' => 'punching_bag', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 3, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 60, 'activityCategory' => 'combat', 'description' => 'Strike a heavy bag in timed rounds to develop power and cardio endurance.'],
            ['name' => 'Speed Bag Drill', 'slug' => 'speed-bag-drill', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['shoulders'], 'equipment' => 'punching_bag', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 2, 'defaultRepsMax' => 3, 'defaultRestSeconds' => 45, 'activityCategory' => 'combat', 'description' => 'Rhythmically strike the speed bag for hand-eye coordination and shoulder endurance.'],
            ['name' => 'Jab-Cross Combo', 'slug' => 'jab-cross-combo', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['shoulders', 'triceps', 'core'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 45, 'activityCategory' => 'combat', 'description' => 'Alternating jab and cross punches for fundamental striking technique.'],
            ['name' => 'Roundhouse Kick Drill', 'slug' => 'roundhouse-kick-drill', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'core'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'activityCategory' => 'combat', 'description' => 'Practice roundhouse kicks to develop hip rotation, leg power, and balance.'],
            ['name' => 'Wrestling Takedown Drill', 'slug' => 'wrestling-takedown-drill', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['core', 'back'], 'equipment' => 'bodyweight', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 90, 'activityCategory' => 'combat', 'description' => 'Drill single-leg and double-leg takedown entries for wrestling technique.'],
            ['name' => 'Slip and Counter Drill', 'slug' => 'slip-and-counter-drill', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['shoulders'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 45, 'activityCategory' => 'combat', 'description' => 'Practice head slips followed by counter punches for defensive boxing skill.'],
            ['name' => 'Sparring Rounds', 'slug' => 'sparring-rounds', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core', 'chest'], 'equipment' => 'bodyweight', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 3, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 60, 'activityCategory' => 'combat', 'description' => 'Timed sparring rounds combining offense and defense under fatigue.'],
            ['name' => 'Footwork Ladder Drill', 'slug' => 'footwork-ladder-drill', 'primaryMuscle' => 'calves', 'secondaryMuscles' => ['quads'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 4, 'defaultRepsMax' => 6, 'defaultRestSeconds' => 45, 'activityCategory' => 'combat', 'description' => 'Quick-feet ladder patterns to build boxing footwork and agility.'],
            ['name' => 'Guard Position Hold', 'slug' => 'guard-position-hold', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 30, 'activityCategory' => 'combat', 'description' => 'Hold hands up in guard position for shoulder endurance and combat readiness.'],

            // === RUNNING (8 exercises) ===
            ['name' => 'Easy Run', 'slug' => 'easy-run', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['hamstrings', 'calves', 'glutes'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 40, 'defaultRestSeconds' => 0, 'activityCategory' => 'running', 'description' => 'Low-intensity steady-state run at conversational pace for aerobic base building.'],
            ['name' => 'Tempo Run', 'slug' => 'tempo-run', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['hamstrings', 'calves', 'core'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 40, 'defaultRestSeconds' => 0, 'activityCategory' => 'running', 'description' => 'Sustained run at lactate threshold pace to improve speed endurance.'],
            ['name' => 'Interval Sprints', 'slug' => 'interval-sprints', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'calves'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 6, 'defaultRepsMin' => 1, 'defaultRepsMax' => 2, 'defaultRestSeconds' => 90, 'activityCategory' => 'running', 'description' => 'Alternating high-intensity sprints with recovery jogs for VO2max improvement.'],
            ['name' => 'Hill Repeats', 'slug' => 'hill-repeats', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['quads', 'calves'], 'equipment' => 'outdoor', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 6, 'defaultRepsMin' => 1, 'defaultRepsMax' => 2, 'defaultRestSeconds' => 120, 'activityCategory' => 'running', 'description' => 'Repeated hill sprints for leg power and running economy improvement.'],
            ['name' => 'Fartlek Run', 'slug' => 'fartlek-run', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['hamstrings', 'calves'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 1, 'defaultRepsMin' => 25, 'defaultRepsMax' => 45, 'defaultRestSeconds' => 0, 'activityCategory' => 'running', 'description' => 'Unstructured speed play alternating fast and slow segments during a run.'],
            ['name' => 'Long Distance Run', 'slug' => 'long-distance-run', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['hamstrings', 'glutes', 'calves', 'core'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 45, 'defaultRepsMax' => 90, 'defaultRestSeconds' => 0, 'activityCategory' => 'running', 'description' => 'Extended duration run at easy pace for endurance and mental toughness.'],
            ['name' => 'Recovery Jog', 'slug' => 'recovery-jog', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['calves'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 5, 'defaultSets' => 1, 'defaultRepsMin' => 15, 'defaultRepsMax' => 25, 'defaultRestSeconds' => 0, 'activityCategory' => 'running', 'description' => 'Very easy jog for active recovery between hard training days.'],
            ['name' => 'Sprint Intervals', 'slug' => 'sprint-intervals', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'hamstrings'], 'equipment' => 'no_equipment', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 8, 'defaultRepsMin' => 1, 'defaultRepsMax' => 1, 'defaultRestSeconds' => 120, 'activityCategory' => 'running', 'description' => 'All-out sprint repeats with full recovery for maximum speed development.'],

            // === WALKING (6 exercises) ===
            ['name' => 'Brisk Walk', 'slug' => 'brisk-walk', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['calves', 'glutes'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 45, 'defaultRestSeconds' => 0, 'activityCategory' => 'walking', 'description' => 'Fast-paced walking at an elevated heart rate for cardiovascular health.'],
            ['name' => 'Incline Walk', 'slug' => 'incline-walk', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['quads', 'calves'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 40, 'defaultRestSeconds' => 0, 'activityCategory' => 'walking', 'description' => 'Walking on an incline or treadmill gradient for glute activation and calorie burn.'],
            ['name' => 'Stair Climbing Intervals', 'slug' => 'stair-climbing-intervals', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'calves'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 5, 'defaultRepsMin' => 2, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 60, 'activityCategory' => 'walking', 'description' => 'Alternating fast stair climbs with walk-down recovery for leg endurance.'],
            ['name' => 'Power Walking', 'slug' => 'power-walking', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['core', 'calves'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 1, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 0, 'activityCategory' => 'walking', 'description' => 'Vigorous walking with arm swings for total body low-impact cardio.'],
            ['name' => 'Hiking Trail Walk', 'slug' => 'hiking-trail-walk', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['quads', 'calves', 'core'], 'equipment' => 'outdoor', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 45, 'defaultRepsMax' => 120, 'defaultRestSeconds' => 0, 'activityCategory' => 'walking', 'description' => 'Trail hiking on uneven terrain for balance, leg strength, and endurance.'],
            ['name' => 'Nordic Walking', 'slug' => 'nordic-walking', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core', 'quads'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 1, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 0, 'activityCategory' => 'walking', 'description' => 'Walking with poles engaging upper body for a full-body low-impact workout.'],

            // === CYCLING (8 exercises) ===
            ['name' => 'Steady State Ride', 'slug' => 'steady-state-ride', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['hamstrings', 'glutes', 'calves'], 'equipment' => 'bike', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 0, 'activityCategory' => 'cycling', 'description' => 'Moderate-intensity sustained cycling for aerobic base and leg endurance.'],
            ['name' => 'Interval Cycling', 'slug' => 'interval-cycling', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'calves'], 'equipment' => 'bike', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 6, 'defaultRepsMin' => 1, 'defaultRepsMax' => 3, 'defaultRestSeconds' => 90, 'activityCategory' => 'cycling', 'description' => 'Alternating high and low intensity cycling intervals for power development.'],
            ['name' => 'Hill Climb Ride', 'slug' => 'hill-climb-ride', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['quads', 'hamstrings'], 'equipment' => 'bike', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 45, 'defaultRestSeconds' => 0, 'activityCategory' => 'cycling', 'description' => 'Cycling with heavy resistance or real hills for climbing power.'],
            ['name' => 'Sprint Cycling', 'slug' => 'sprint-cycling', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'calves'], 'equipment' => 'bike', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 8, 'defaultRepsMin' => 1, 'defaultRepsMax' => 1, 'defaultRestSeconds' => 120, 'activityCategory' => 'cycling', 'description' => 'All-out cycling sprints for maximum power and anaerobic capacity.'],
            ['name' => 'Endurance Ride', 'slug' => 'endurance-ride', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['hamstrings', 'calves'], 'equipment' => 'bike', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 1, 'defaultRepsMin' => 60, 'defaultRepsMax' => 120, 'defaultRestSeconds' => 0, 'activityCategory' => 'cycling', 'description' => 'Long duration easy cycling for building aerobic endurance.'],
            ['name' => 'Cadence Drill', 'slug' => 'cadence-drill', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['calves'], 'equipment' => 'bike', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 4, 'defaultRepsMin' => 3, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 60, 'activityCategory' => 'cycling', 'description' => 'High-cadence low-resistance spinning to improve pedaling efficiency.'],
            ['name' => 'Standing Climb', 'slug' => 'standing-climb', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['quads', 'core'], 'equipment' => 'bike', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 4, 'defaultRepsMin' => 2, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 90, 'activityCategory' => 'cycling', 'description' => 'Out-of-saddle climbing for full-body cycling power engagement.'],
            ['name' => 'Recovery Spin', 'slug' => 'recovery-spin', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['calves'], 'equipment' => 'bike', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 5, 'defaultSets' => 1, 'defaultRepsMin' => 15, 'defaultRepsMax' => 30, 'defaultRestSeconds' => 0, 'activityCategory' => 'cycling', 'description' => 'Very easy spinning for active recovery and blood flow.'],

            // === SWIMMING (8 exercises) ===
            ['name' => 'Freestyle Laps', 'slug' => 'freestyle-laps', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders', 'core'], 'equipment' => 'pool', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 2, 'defaultRepsMax' => 4, 'defaultRestSeconds' => 30, 'activityCategory' => 'swimming', 'description' => 'Front crawl swimming for full-body cardio and back development.'],
            ['name' => 'Backstroke Laps', 'slug' => 'backstroke-laps', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders', 'triceps'], 'equipment' => 'pool', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 4, 'defaultRepsMin' => 2, 'defaultRepsMax' => 4, 'defaultRestSeconds' => 30, 'activityCategory' => 'swimming', 'description' => 'Supine swimming stroke for back and shoulder development with open breathing.'],
            ['name' => 'Butterfly Stroke', 'slug' => 'butterfly-stroke', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['back', 'core', 'chest'], 'equipment' => 'pool', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 1, 'defaultRepsMax' => 2, 'defaultRestSeconds' => 60, 'activityCategory' => 'swimming', 'description' => 'Demanding simultaneous arm recovery stroke for total upper body power.'],
            ['name' => 'Breaststroke Laps', 'slug' => 'breaststroke-laps', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['shoulders', 'quads'], 'equipment' => 'pool', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 4, 'defaultRepsMin' => 2, 'defaultRepsMax' => 4, 'defaultRestSeconds' => 30, 'activityCategory' => 'swimming', 'description' => 'Symmetrical stroke emphasizing chest and inner thigh muscles.'],
            ['name' => 'Kickboard Drill', 'slug' => 'kickboard-drill', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['calves', 'glutes'], 'equipment' => 'pool', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 4, 'defaultRepsMin' => 2, 'defaultRepsMax' => 4, 'defaultRestSeconds' => 30, 'activityCategory' => 'swimming', 'description' => 'Kicking with a board for isolated leg conditioning in the pool.'],
            ['name' => 'Pull Buoy Drill', 'slug' => 'pull-buoy-drill', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders'], 'equipment' => 'pool', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 4, 'defaultRepsMin' => 2, 'defaultRepsMax' => 4, 'defaultRestSeconds' => 30, 'activityCategory' => 'swimming', 'description' => 'Upper-body focused swimming with a buoy between the legs.'],
            ['name' => 'Treading Water', 'slug' => 'treading-water', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['shoulders', 'quads'], 'equipment' => 'pool', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 2, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 60, 'activityCategory' => 'swimming', 'description' => 'Staying afloat in deep water for core and total body endurance.'],
            ['name' => 'Sprint Laps', 'slug' => 'sprint-laps', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders', 'core'], 'equipment' => 'pool', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 6, 'defaultRepsMin' => 1, 'defaultRepsMax' => 2, 'defaultRestSeconds' => 60, 'activityCategory' => 'swimming', 'description' => 'All-out swimming sprints for anaerobic power in the pool.'],

            // === FLEXIBILITY/YOGA (12 exercises) ===
            ['name' => 'Sun Salutation', 'slug' => 'sun-salutation', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['shoulders', 'hamstrings'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 3, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 30, 'activityCategory' => 'flexibility', 'description' => 'Classic yoga flow sequence linking breath to movement for full-body warm-up.'],
            ['name' => 'Downward Dog', 'slug' => 'downward-dog', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['hamstrings', 'calves'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 15, 'activityCategory' => 'flexibility', 'description' => 'Inverted V position stretching hamstrings, calves, and shoulders.'],
            ['name' => 'Warrior I', 'slug' => 'warrior-i', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['shoulders', 'core'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 15, 'activityCategory' => 'flexibility', 'description' => 'Lunge stance with arms overhead for hip flexibility and leg strength.'],
            ['name' => 'Warrior II', 'slug' => 'warrior-ii', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['shoulders', 'core'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 15, 'activityCategory' => 'flexibility', 'description' => 'Wide stance with arms extended for hip opening and leg endurance.'],
            ['name' => 'Tree Pose', 'slug' => 'tree-pose', 'primaryMuscle' => 'calves', 'secondaryMuscles' => ['core'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 15, 'activityCategory' => 'flexibility', 'description' => 'Single-leg balance pose for stability and ankle strength.'],
            ['name' => 'Pigeon Pose', 'slug' => 'pigeon-pose', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['hamstrings'], 'equipment' => 'mat', 'difficulty' => 'intermediate', 'movementType' => 'isolation', 'priority' => 3, 'defaultSets' => 2, 'defaultRepsMin' => 30, 'defaultRepsMax' => 90, 'defaultRestSeconds' => 15, 'activityCategory' => 'flexibility', 'description' => 'Deep hip opener stretching the glutes and hip flexors.'],
            ['name' => 'Cobra Pose', 'slug' => 'cobra-pose', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['core'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 3, 'defaultRepsMin' => 20, 'defaultRepsMax' => 30, 'defaultRestSeconds' => 15, 'activityCategory' => 'flexibility', 'description' => 'Prone back extension for spinal flexibility and chest opening.'],
            ['name' => 'Child\'s Pose', 'slug' => 'childs-pose', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 2, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 0, 'activityCategory' => 'flexibility', 'description' => 'Resting pose for gentle back and shoulder stretch with deep breathing.'],
            ['name' => 'Bridge Pose', 'slug' => 'bridge-pose', 'primaryMuscle' => 'glutes', 'secondaryMuscles' => ['hamstrings', 'core'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 15, 'activityCategory' => 'flexibility', 'description' => 'Supine hip lift for glute activation and spinal mobility.'],
            ['name' => 'Plank to Downward Dog', 'slug' => 'plank-to-downward-dog', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['shoulders'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 30, 'activityCategory' => 'flexibility', 'description' => 'Dynamic flow between plank and downward dog for core and shoulder work.'],
            ['name' => 'Seated Forward Fold', 'slug' => 'seated-forward-fold', 'primaryMuscle' => 'hamstrings', 'secondaryMuscles' => ['back'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 2, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 15, 'activityCategory' => 'flexibility', 'description' => 'Seated hamstring stretch reaching toward the toes.'],
            ['name' => 'Cat-Cow Stretch', 'slug' => 'cat-cow-stretch', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['core'], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 15, 'activityCategory' => 'flexibility', 'description' => 'Alternating spinal flexion and extension for back mobility.'],

            // === CARDIO/HIIT (10 exercises) ===
            ['name' => 'Burpees', 'slug' => 'burpees', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['quads', 'shoulders', 'core'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'activityCategory' => 'cardio', 'description' => 'Full-body explosive movement combining squat, push-up, and jump.'],
            ['name' => 'Box Jumps', 'slug' => 'box-jumps', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'calves'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 90, 'activityCategory' => 'cardio', 'description' => 'Explosive jump onto an elevated box for lower body power.'],
            ['name' => 'Jump Rope Basic', 'slug' => 'jump-rope-basic', 'primaryMuscle' => 'calves', 'secondaryMuscles' => ['shoulders', 'core'], 'equipment' => 'jump_rope', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 2, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 30, 'activityCategory' => 'cardio', 'description' => 'Basic single-bounce jump rope for cardio conditioning and coordination.'],
            ['name' => 'Double Unders', 'slug' => 'double-unders', 'primaryMuscle' => 'calves', 'secondaryMuscles' => ['shoulders', 'core'], 'equipment' => 'jump_rope', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 10, 'defaultRepsMax' => 30, 'defaultRestSeconds' => 60, 'activityCategory' => 'cardio', 'description' => 'Jump rope passing under feet twice per jump for advanced conditioning.'],
            ['name' => 'Battle Ropes', 'slug' => 'battle-ropes', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core', 'back'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 20, 'defaultRepsMax' => 40, 'defaultRestSeconds' => 60, 'activityCategory' => 'cardio', 'description' => 'Alternating or simultaneous rope waves for upper body and cardio.'],
            ['name' => 'Jumping Jacks', 'slug' => 'jumping-jacks', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['calves'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 20, 'defaultRepsMax' => 50, 'defaultRestSeconds' => 30, 'activityCategory' => 'cardio', 'description' => 'Classic full-body warm-up and cardio exercise with arm and leg spread.'],
            ['name' => 'High Knees', 'slug' => 'high-knees', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['core', 'calves'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 20, 'defaultRepsMax' => 40, 'defaultRestSeconds' => 30, 'activityCategory' => 'cardio', 'description' => 'Running in place driving knees high for cardio and hip flexor activation.'],
            ['name' => 'Kettlebell Snatch', 'slug' => 'kettlebell-snatch', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['back', 'glutes', 'core'], 'equipment' => 'kettlebell', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 90, 'activityCategory' => 'cardio', 'description' => 'Explosive single-arm kettlebell lift from floor to overhead in one motion.'],
            ['name' => 'Rowing Machine Sprint', 'slug' => 'rowing-machine-sprint', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['quads', 'biceps', 'core'], 'equipment' => 'rowing_machine', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 5, 'defaultRepsMin' => 1, 'defaultRepsMax' => 2, 'defaultRestSeconds' => 90, 'activityCategory' => 'cardio', 'description' => 'High-intensity rowing intervals for total body conditioning.'],
            ['name' => 'Assault Bike Sprint', 'slug' => 'assault-bike-sprint', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['shoulders', 'core'], 'equipment' => 'bike', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 5, 'defaultRepsMin' => 1, 'defaultRepsMax' => 1, 'defaultRestSeconds' => 90, 'activityCategory' => 'cardio', 'description' => 'All-out assault bike sprints for maximum calorie burn and conditioning.'],

            // === DANCE (6 exercises) ===
            ['name' => 'Cardio Dance Basics', 'slug' => 'cardio-dance-basics', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['calves', 'core'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 40, 'defaultRestSeconds' => 0, 'activityCategory' => 'dance', 'description' => 'Simple dance moves at a cardio pace for fun full-body exercise.'],
            ['name' => 'Salsa Steps', 'slug' => 'salsa-steps', 'primaryMuscle' => 'calves', 'secondaryMuscles' => ['quads', 'core'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 5, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 30, 'activityCategory' => 'dance', 'description' => 'Basic salsa footwork patterns for coordination and rhythm.'],
            ['name' => 'Hip-Hop Cardio', 'slug' => 'hip-hop-cardio', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['quads', 'calves'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 40, 'defaultRestSeconds' => 0, 'activityCategory' => 'dance', 'description' => 'High-energy hip-hop inspired dance workout for cardio and core.'],
            ['name' => 'Dance Fitness Choreography', 'slug' => 'dance-fitness-choreography', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['shoulders', 'quads'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 30, 'defaultRepsMax' => 45, 'defaultRestSeconds' => 0, 'activityCategory' => 'dance', 'description' => 'Choreographed dance routines combining multiple movement patterns.'],
            ['name' => 'Ballet Conditioning', 'slug' => 'ballet-conditioning', 'primaryMuscle' => 'calves', 'secondaryMuscles' => ['quads', 'core'], 'equipment' => 'mat', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 30, 'activityCategory' => 'dance', 'description' => 'Ballet-inspired exercises for posture, leg strength, and flexibility.'],
            ['name' => 'Zumba Session', 'slug' => 'zumba-session', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['core', 'calves'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 0, 'activityCategory' => 'dance', 'description' => 'Latin-inspired dance fitness session for cardio and enjoyment.'],

            // === WINTER SPORTS (6 exercises) ===
            ['name' => 'Ski Conditioning Squats', 'slug' => 'ski-conditioning-squats', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'core'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 12, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'activityCategory' => 'winter_sports', 'description' => 'Squat variations mimicking ski posture for alpine sport conditioning.'],
            ['name' => 'Skating Stride Drill', 'slug' => 'skating-stride-drill', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'core'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 16, 'defaultRestSeconds' => 60, 'activityCategory' => 'winter_sports', 'description' => 'Lateral bounding mimicking skating stride for power and balance.'],
            ['name' => 'Snowboard Balance Drill', 'slug' => 'snowboard-balance-drill', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['quads', 'calves'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 30, 'activityCategory' => 'winter_sports', 'description' => 'Balance board or single-leg stability work for snowboard readiness.'],
            ['name' => 'Ski Jump Training', 'slug' => 'ski-jump-training', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'calves'], 'equipment' => 'bodyweight', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 90, 'activityCategory' => 'winter_sports', 'description' => 'Explosive jump training simulating ski jump take-off mechanics.'],
            ['name' => 'Ice Skating Endurance', 'slug' => 'ice-skating-endurance', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['calves', 'glutes'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 45, 'defaultRestSeconds' => 0, 'activityCategory' => 'winter_sports', 'description' => 'Sustained skating for cardiovascular endurance and leg conditioning.'],
            ['name' => 'Cross-Country Ski Intervals', 'slug' => 'cross-country-ski-intervals', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['shoulders', 'core', 'back'], 'equipment' => 'outdoor', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 5, 'defaultRepsMin' => 3, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 90, 'activityCategory' => 'winter_sports', 'description' => 'High-intensity cross-country skiing intervals for total body fitness.'],

            // === RACQUET SPORTS (6 exercises) ===
            ['name' => 'Serve Practice', 'slug' => 'serve-practice', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core', 'triceps'], 'equipment' => 'racquet', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'activityCategory' => 'racquet_sports', 'description' => 'Repeated serve practice for technique and shoulder conditioning.'],
            ['name' => 'Footwork Drill', 'slug' => 'racquet-footwork-drill', 'primaryMuscle' => 'calves', 'secondaryMuscles' => ['quads'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 5, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 30, 'activityCategory' => 'racquet_sports', 'description' => 'Court footwork patterns for quick direction changes and positioning.'],
            ['name' => 'Rally Endurance', 'slug' => 'rally-endurance', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core', 'quads'], 'equipment' => 'racquet', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 5, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 60, 'activityCategory' => 'racquet_sports', 'description' => 'Extended rally sessions for match stamina and stroke consistency.'],
            ['name' => 'Volley Drill', 'slug' => 'volley-drill', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core'], 'equipment' => 'racquet', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'activityCategory' => 'racquet_sports', 'description' => 'Net volley practice for quick reflexes and racquet control.'],
            ['name' => 'Lateral Movement Drill', 'slug' => 'lateral-movement-drill', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['calves', 'glutes'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 45, 'activityCategory' => 'racquet_sports', 'description' => 'Side-to-side movement drill for court coverage and agility.'],
            ['name' => 'Smash Practice', 'slug' => 'smash-practice', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core', 'triceps'], 'equipment' => 'racquet', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'activityCategory' => 'racquet_sports', 'description' => 'Overhead smash repetitions for power and overhead technique.'],

            // === TEAM SPORTS (6 exercises) ===
            ['name' => 'Agility Ladder Drill', 'slug' => 'agility-ladder-drill', 'primaryMuscle' => 'calves', 'secondaryMuscles' => ['quads', 'core'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 4, 'defaultRepsMax' => 6, 'defaultRestSeconds' => 45, 'activityCategory' => 'team_sports', 'description' => 'Quick-feet patterns through an agility ladder for speed and coordination.'],
            ['name' => 'Sprint Shuttle Run', 'slug' => 'sprint-shuttle-run', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'calves'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 5, 'defaultRepsMin' => 4, 'defaultRepsMax' => 6, 'defaultRestSeconds' => 90, 'activityCategory' => 'team_sports', 'description' => 'Back-and-forth sprints with direction changes for sport-specific speed.'],
            ['name' => 'Passing Drill', 'slug' => 'passing-drill', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'activityCategory' => 'team_sports', 'description' => 'Repeated passing practice for accuracy and upper body coordination.'],
            ['name' => 'Defensive Slide Drill', 'slug' => 'defensive-slide-drill', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'core'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 60, 'activityCategory' => 'team_sports', 'description' => 'Low athletic stance lateral slides for defensive positioning.'],
            ['name' => 'Plyometric Jumps', 'slug' => 'plyometric-jumps', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['glutes', 'calves'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 6, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 90, 'activityCategory' => 'team_sports', 'description' => 'Explosive jump variations for vertical leap and sport power.'],
            ['name' => 'Cone Dribbling', 'slug' => 'cone-dribbling', 'primaryMuscle' => 'calves', 'secondaryMuscles' => ['quads'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 4, 'defaultRepsMax' => 8, 'defaultRestSeconds' => 30, 'activityCategory' => 'team_sports', 'description' => 'Dribbling through cones for ball control and agility.'],

            // === WATER SPORTS (6 exercises) ===
            ['name' => 'Rowing Machine Intervals', 'slug' => 'rowing-machine-intervals', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['quads', 'biceps', 'core'], 'equipment' => 'rowing_machine', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 5, 'defaultRepsMin' => 2, 'defaultRepsMax' => 5, 'defaultRestSeconds' => 90, 'activityCategory' => 'water_sports', 'description' => 'Alternating hard and easy rowing intervals for endurance and power.'],
            ['name' => 'Paddle Stroke Drill', 'slug' => 'paddle-stroke-drill', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders', 'core'], 'equipment' => 'outdoor', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 60, 'activityCategory' => 'water_sports', 'description' => 'Paddle technique practice for kayaking or canoeing efficiency.'],
            ['name' => 'Surf Pop-Up Drill', 'slug' => 'surf-pop-up-drill', 'primaryMuscle' => 'chest', 'secondaryMuscles' => ['core', 'shoulders'], 'equipment' => 'mat', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 4, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 45, 'activityCategory' => 'water_sports', 'description' => 'Explosive push-up to standing on a mat simulating surfboard pop-up.'],
            ['name' => 'Kayak Endurance Paddle', 'slug' => 'kayak-endurance-paddle', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders', 'core', 'biceps'], 'equipment' => 'outdoor', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 30, 'defaultRepsMax' => 60, 'defaultRestSeconds' => 0, 'activityCategory' => 'water_sports', 'description' => 'Sustained kayaking for upper body endurance and back conditioning.'],
            ['name' => 'Swimming Open Water', 'slug' => 'swimming-open-water', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders', 'core'], 'equipment' => 'pool', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 45, 'defaultRestSeconds' => 0, 'activityCategory' => 'water_sports', 'description' => 'Open water swimming for endurance and mental toughness.'],
            ['name' => 'Sailing Fitness Circuit', 'slug' => 'sailing-fitness-circuit', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['back', 'shoulders'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 8, 'defaultRepsMax' => 12, 'defaultRestSeconds' => 60, 'activityCategory' => 'water_sports', 'description' => 'Circuit of exercises mimicking sailing movements for sport-specific fitness.'],

            // === OUTDOOR (6 exercises) ===
            ['name' => 'Rock Climbing Holds', 'slug' => 'rock-climbing-holds', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps', 'core'], 'equipment' => 'outdoor', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 5, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 120, 'activityCategory' => 'outdoor', 'description' => 'Climbing wall or rock face hold practice for grip and back strength.'],
            ['name' => 'Archery Draw Practice', 'slug' => 'archery-draw-practice', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['shoulders', 'biceps'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'activityCategory' => 'outdoor', 'description' => 'Bow drawing practice for rear deltoid and back isometric strength.'],
            ['name' => 'Golf Swing Drill', 'slug' => 'golf-swing-drill', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['shoulders'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 30, 'activityCategory' => 'outdoor', 'description' => 'Rotational swing practice for core power and golf technique.'],
            ['name' => 'Trail Obstacle Course', 'slug' => 'trail-obstacle-course', 'primaryMuscle' => 'quads', 'secondaryMuscles' => ['core', 'glutes'], 'equipment' => 'outdoor', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 5, 'defaultRepsMax' => 10, 'defaultRestSeconds' => 90, 'activityCategory' => 'outdoor', 'description' => 'Navigate natural terrain obstacles for functional fitness.'],
            ['name' => 'Horseback Riding Conditioning', 'slug' => 'horseback-riding-conditioning', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['quads', 'glutes'], 'equipment' => 'bodyweight', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'activityCategory' => 'outdoor', 'description' => 'Exercises mimicking riding posture for core and hip stability.'],
            ['name' => 'Bouldering Traverse', 'slug' => 'bouldering-traverse', 'primaryMuscle' => 'back', 'secondaryMuscles' => ['biceps', 'core', 'shoulders'], 'equipment' => 'outdoor', 'difficulty' => 'advanced', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 3, 'defaultRepsMax' => 6, 'defaultRestSeconds' => 120, 'activityCategory' => 'outdoor', 'description' => 'Horizontal climbing traverse for grip endurance and technique.'],

            // === MIND & BODY (6 exercises) ===
            ['name' => 'Guided Meditation', 'slug' => 'guided-meditation', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 1, 'defaultRepsMin' => 10, 'defaultRepsMax' => 30, 'defaultRestSeconds' => 0, 'activityCategory' => 'mind_body', 'description' => 'Seated or lying meditation with guided instruction for mental clarity.'],
            ['name' => 'Deep Breathing Exercise', 'slug' => 'deep-breathing-exercise', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 1, 'defaultRepsMin' => 5, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 0, 'activityCategory' => 'mind_body', 'description' => 'Diaphragmatic breathing exercises for stress reduction and recovery.'],
            ['name' => 'Body Scan Relaxation', 'slug' => 'body-scan-relaxation', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 5, 'defaultSets' => 1, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 0, 'activityCategory' => 'mind_body', 'description' => 'Progressive awareness scan through body regions for deep relaxation.'],
            ['name' => 'Progressive Muscle Relaxation', 'slug' => 'progressive-muscle-relaxation', 'primaryMuscle' => 'core', 'secondaryMuscles' => [], 'equipment' => 'mat', 'difficulty' => 'beginner', 'movementType' => 'isolation', 'priority' => 4, 'defaultSets' => 1, 'defaultRepsMin' => 10, 'defaultRepsMax' => 20, 'defaultRestSeconds' => 0, 'activityCategory' => 'mind_body', 'description' => 'Systematic tensing and releasing of muscle groups for recovery.'],
            ['name' => 'Tai Chi Flow', 'slug' => 'tai-chi-flow', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['quads', 'shoulders'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 1, 'defaultRepsMin' => 15, 'defaultRepsMax' => 30, 'defaultRestSeconds' => 0, 'activityCategory' => 'mind_body', 'description' => 'Slow flowing movements for balance, coordination, and mindfulness.'],
            ['name' => 'Qi Gong Routine', 'slug' => 'qi-gong-routine', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 1, 'defaultRepsMin' => 15, 'defaultRepsMax' => 30, 'defaultRestSeconds' => 0, 'activityCategory' => 'mind_body', 'description' => 'Gentle rhythmic movements coordinated with breath for energy cultivation.'],

            // === OTHER (4 exercises) ===
            ['name' => 'Wheelchair Sprint Drill', 'slug' => 'wheelchair-sprint-drill', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['back', 'biceps'], 'equipment' => 'no_equipment', 'difficulty' => 'intermediate', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 5, 'defaultRepsMin' => 1, 'defaultRepsMax' => 2, 'defaultRestSeconds' => 90, 'activityCategory' => 'other', 'description' => 'Wheelchair sprints for upper body power and cardiovascular fitness.'],
            ['name' => 'Fitness Gaming Session', 'slug' => 'fitness-gaming-session', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['quads'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 2, 'defaultSets' => 1, 'defaultRepsMin' => 20, 'defaultRepsMax' => 45, 'defaultRestSeconds' => 0, 'activityCategory' => 'other', 'description' => 'Active video gaming session using motion-based fitness games.'],
            ['name' => 'Bowling Practice', 'slug' => 'bowling-practice', 'primaryMuscle' => 'shoulders', 'secondaryMuscles' => ['core'], 'equipment' => 'no_equipment', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 3, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 30, 'activityCategory' => 'other', 'description' => 'Bowling frame practice for shoulder coordination and release technique.'],
            ['name' => 'General Conditioning Circuit', 'slug' => 'general-conditioning-circuit', 'primaryMuscle' => 'core', 'secondaryMuscles' => ['quads', 'shoulders'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'movementType' => 'compound', 'priority' => 1, 'defaultSets' => 3, 'defaultRepsMin' => 10, 'defaultRepsMax' => 15, 'defaultRestSeconds' => 60, 'activityCategory' => 'other', 'description' => 'Mixed bodyweight circuit for general fitness and conditioning.'],
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
