<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Entity\User;
use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Entity\WorkoutPlanExercise;
use App\Domain\Workout\Enum\MuscleGroup;
use App\Domain\Workout\Enum\WorkoutPlanStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the WorkoutPlan entity.
 *
 * Verifies UUID generation, default status, getter/setter contracts,
 * nullable fields, exercise collection management, and setter chaining.
 */
class WorkoutPlanEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $plan = new WorkoutPlan();

        $this->assertInstanceOf(Uuid::class, $plan->getId());
    }

    public function testCreationSetsDefaultStatus(): void
    {
        $plan = new WorkoutPlan();

        $this->assertSame(WorkoutPlanStatus::Pending, $plan->getStatus());
    }

    public function testCreationSetsCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $plan = new WorkoutPlan();
        $after = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $plan->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $plan->getCreatedAt());
        $this->assertLessThanOrEqual($after, $plan->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $plan = new WorkoutPlan();
        $user = new User();
        $plannedAt = new \DateTimeImmutable('2026-04-01T09:00:00+00:00');
        $startedAt = new \DateTimeImmutable('2026-04-01T09:05:00+00:00');
        $completedAt = new \DateTimeImmutable('2026-04-01T10:00:00+00:00');

        $plan->setUser($user)
            ->setName('Chest & Triceps Day')
            ->setStatus(WorkoutPlanStatus::InProgress)
            ->setActivityType('strength_training')
            ->setTargetMuscleGroups(['chest', 'triceps'])
            ->setPlannedAt($plannedAt)
            ->setStartedAt($startedAt)
            ->setCompletedAt($completedAt)
            ->setTargetDistance(5000.0)
            ->setTargetDuration(60)
            ->setTargetCalories(500.0)
            ->setRewardTiers(['bronze' => ['threshold' => 3000, 'xp' => 50]]);

        $this->assertSame($user, $plan->getUser());
        $this->assertSame('Chest & Triceps Day', $plan->getName());
        $this->assertSame(WorkoutPlanStatus::InProgress, $plan->getStatus());
        $this->assertSame('strength_training', $plan->getActivityType());
        $this->assertSame(['chest', 'triceps'], $plan->getTargetMuscleGroups());
        $this->assertEquals($plannedAt, $plan->getPlannedAt());
        $this->assertEquals($startedAt, $plan->getStartedAt());
        $this->assertEquals($completedAt, $plan->getCompletedAt());
        $this->assertSame(5000.0, $plan->getTargetDistance());
        $this->assertSame(60, $plan->getTargetDuration());
        $this->assertSame(500.0, $plan->getTargetCalories());
        $this->assertSame(['bronze' => ['threshold' => 3000, 'xp' => 50]], $plan->getRewardTiers());
    }

    public function testNullableFields(): void
    {
        $plan = new WorkoutPlan();

        $plan->setActivityType(null);
        $plan->setTargetMuscleGroups(null);
        $plan->setStartedAt(null);
        $plan->setCompletedAt(null);
        $plan->setTargetDistance(null);
        $plan->setTargetDuration(null);
        $plan->setTargetCalories(null);
        $plan->setRewardTiers(null);

        $this->assertNull($plan->getActivityType());
        $this->assertNull($plan->getTargetMuscleGroups());
        $this->assertNull($plan->getStartedAt());
        $this->assertNull($plan->getCompletedAt());
        $this->assertNull($plan->getTargetDistance());
        $this->assertNull($plan->getTargetDuration());
        $this->assertNull($plan->getTargetCalories());
        $this->assertNull($plan->getRewardTiers());
    }

    public function testExerciseCollectionManagement(): void
    {
        $plan = new WorkoutPlan();
        $plan->setName('Test Plan');

        $exercise = new Exercise();
        $exercise->setName('Bench Press')
            ->setSlug('bench-press')
            ->setPrimaryMuscle(MuscleGroup::Chest)
            ->setEquipment(\App\Domain\Workout\Enum\Equipment::Barbell)
            ->setDifficulty(\App\Domain\Workout\Enum\ExerciseDifficulty::Beginner)
            ->setMovementType(\App\Domain\Workout\Enum\ExerciseMovementType::Compound)
            ->setPriority(1);

        $planExercise = new WorkoutPlanExercise();
        $planExercise->setExercise($exercise)
            ->setOrderIndex(1)
            ->setSets(3)
            ->setRepsMin(8)
            ->setRepsMax(12)
            ->setRestSeconds(90);

        $plan->addExercise($planExercise);

        $this->assertCount(1, $plan->getExercises());
        $this->assertSame($plan, $planExercise->getWorkoutPlan());

        // Adding same exercise again should not duplicate
        $plan->addExercise($planExercise);
        $this->assertCount(1, $plan->getExercises());

        $plan->removeExercise($planExercise);
        $this->assertCount(0, $plan->getExercises());
    }

    public function testToString(): void
    {
        $plan = new WorkoutPlan();
        $plan->setName('Leg Day');

        $this->assertSame('Leg Day', (string) $plan);
    }

    public function testSetterChaining(): void
    {
        $plan = new WorkoutPlan();
        $user = new User();

        $result = $plan->setUser($user)
            ->setName('Test')
            ->setStatus(WorkoutPlanStatus::Completed)
            ->setActivityType('running')
            ->setTargetMuscleGroups(['quads'])
            ->setPlannedAt(new \DateTimeImmutable())
            ->setStartedAt(null)
            ->setCompletedAt(null)
            ->setTargetDistance(null)
            ->setTargetDuration(null)
            ->setTargetCalories(null)
            ->setRewardTiers(null);

        $this->assertSame($plan, $result);
    }
}
