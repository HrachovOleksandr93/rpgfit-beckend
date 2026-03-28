<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Media\Entity\MediaFile;
use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Enum\Equipment;
use App\Domain\Workout\Enum\ExerciseDifficulty;
use App\Domain\Workout\Enum\ExerciseMovementType;
use App\Domain\Workout\Enum\MuscleGroup;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the Exercise entity.
 *
 * Verifies UUID generation, getter/setter contracts, default values,
 * nullable fields, and setter chaining for the exercise catalog entity.
 */
class ExerciseEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $exercise = new Exercise();

        $this->assertInstanceOf(Uuid::class, $exercise->getId());
    }

    public function testSettersAndGetters(): void
    {
        $exercise = new Exercise();

        $exercise->setName('Barbell Bench Press')
            ->setSlug('barbell-bench-press')
            ->setPrimaryMuscle(MuscleGroup::Chest)
            ->setSecondaryMuscles(['triceps', 'shoulders'])
            ->setEquipment(Equipment::Barbell)
            ->setDifficulty(ExerciseDifficulty::Beginner)
            ->setMovementType(ExerciseMovementType::Compound)
            ->setPriority(1)
            ->setIsBaseExercise(true)
            ->setDescription('A great compound movement.')
            ->setDefaultSets(4)
            ->setDefaultRepsMin(6)
            ->setDefaultRepsMax(10)
            ->setDefaultRestSeconds(120);

        $this->assertSame('Barbell Bench Press', $exercise->getName());
        $this->assertSame('barbell-bench-press', $exercise->getSlug());
        $this->assertSame(MuscleGroup::Chest, $exercise->getPrimaryMuscle());
        $this->assertSame(['triceps', 'shoulders'], $exercise->getSecondaryMuscles());
        $this->assertSame(Equipment::Barbell, $exercise->getEquipment());
        $this->assertSame(ExerciseDifficulty::Beginner, $exercise->getDifficulty());
        $this->assertSame(ExerciseMovementType::Compound, $exercise->getMovementType());
        $this->assertSame(1, $exercise->getPriority());
        $this->assertTrue($exercise->isBaseExercise());
        $this->assertSame('A great compound movement.', $exercise->getDescription());
        $this->assertSame(4, $exercise->getDefaultSets());
        $this->assertSame(6, $exercise->getDefaultRepsMin());
        $this->assertSame(10, $exercise->getDefaultRepsMax());
        $this->assertSame(120, $exercise->getDefaultRestSeconds());
    }

    public function testDefaultValues(): void
    {
        $exercise = new Exercise();

        $this->assertSame(3, $exercise->getDefaultSets());
        $this->assertSame(8, $exercise->getDefaultRepsMin());
        $this->assertSame(12, $exercise->getDefaultRepsMax());
        $this->assertSame(90, $exercise->getDefaultRestSeconds());
        $this->assertFalse($exercise->isBaseExercise());
        $this->assertSame([], $exercise->getSecondaryMuscles());
    }

    public function testNullableFields(): void
    {
        $exercise = new Exercise();

        $exercise->setDescription(null);
        $exercise->setImage(null);

        $this->assertNull($exercise->getDescription());
        $this->assertNull($exercise->getImage());
    }

    public function testImageRelation(): void
    {
        $exercise = new Exercise();
        $image = new MediaFile();

        $exercise->setImage($image);

        $this->assertSame($image, $exercise->getImage());
    }

    public function testSetterChaining(): void
    {
        $exercise = new Exercise();

        $result = $exercise->setName('Test')
            ->setSlug('test')
            ->setPrimaryMuscle(MuscleGroup::Back)
            ->setSecondaryMuscles([])
            ->setEquipment(Equipment::Bodyweight)
            ->setDifficulty(ExerciseDifficulty::Intermediate)
            ->setMovementType(ExerciseMovementType::Isolation)
            ->setPriority(3)
            ->setIsBaseExercise(false)
            ->setDescription('Test description')
            ->setImage(null)
            ->setDefaultSets(3)
            ->setDefaultRepsMin(10)
            ->setDefaultRepsMax(15)
            ->setDefaultRestSeconds(60);

        $this->assertSame($exercise, $result);
    }

    public function testToString(): void
    {
        $exercise = new Exercise();
        $exercise->setName('Pull-ups');

        $this->assertSame('Pull-ups', (string) $exercise);
    }
}
