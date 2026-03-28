<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Training\Entity\ExerciseType;
use App\Domain\Training\Entity\WorkoutCategory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class WorkoutCategoryEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $category = new WorkoutCategory();

        $this->assertInstanceOf(Uuid::class, $category->getId());
    }

    public function testSettersAndGetters(): void
    {
        $category = new WorkoutCategory();

        $category->setName('Strength Training');
        $category->setSlug('strength-training');
        $category->setDescription('Exercises focused on building muscle strength');

        $this->assertSame('Strength Training', $category->getName());
        $this->assertSame('strength-training', $category->getSlug());
        $this->assertSame('Exercises focused on building muscle strength', $category->getDescription());
    }

    public function testDescriptionIsNullable(): void
    {
        $category = new WorkoutCategory();

        $category->setDescription(null);

        $this->assertNull($category->getDescription());
    }

    public function testToStringReturnsName(): void
    {
        $category = new WorkoutCategory();
        $category->setName('Cardio');

        $this->assertSame('Cardio', (string) $category);
    }

    public function testExerciseTypesCollection(): void
    {
        $category = new WorkoutCategory();

        $this->assertCount(0, $category->getExerciseTypes());

        $exerciseType = new ExerciseType();
        $category->addExerciseType($exerciseType);

        $this->assertCount(1, $category->getExerciseTypes());
        $this->assertTrue($category->getExerciseTypes()->contains($exerciseType));
        $this->assertSame($category, $exerciseType->getCategory());

        $category->removeExerciseType($exerciseType);

        $this->assertCount(0, $category->getExerciseTypes());
    }

    public function testSetterChaining(): void
    {
        $category = new WorkoutCategory();

        $result = $category->setName('Flexibility')
            ->setSlug('flexibility')
            ->setDescription('Stretching exercises');

        $this->assertSame($category, $result);
    }
}
