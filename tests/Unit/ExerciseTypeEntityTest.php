<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Training\Entity\ExerciseStatReward;
use App\Domain\Training\Entity\ExerciseType;
use App\Domain\Training\Entity\WorkoutCategory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ExerciseTypeEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $type = new ExerciseType();

        $this->assertInstanceOf(Uuid::class, $type->getId());
    }

    public function testSettersAndGetters(): void
    {
        $type = new ExerciseType();
        $category = new WorkoutCategory();

        $type->setCategory($category);
        $type->setName('Bench Press');
        $type->setSlug('bench-press');
        $type->setDescription('A compound chest exercise');

        $this->assertSame($category, $type->getCategory());
        $this->assertSame('Bench Press', $type->getName());
        $this->assertSame('bench-press', $type->getSlug());
        $this->assertSame('A compound chest exercise', $type->getDescription());
    }

    public function testDescriptionIsNullable(): void
    {
        $type = new ExerciseType();

        $type->setDescription(null);

        $this->assertNull($type->getDescription());
    }

    public function testToStringReturnsName(): void
    {
        $type = new ExerciseType();
        $type->setName('Squat');

        $this->assertSame('Squat', (string) $type);
    }

    public function testStatRewardsCollection(): void
    {
        $type = new ExerciseType();

        $this->assertCount(0, $type->getStatRewards());

        $reward = new ExerciseStatReward();
        $type->addStatReward($reward);

        $this->assertCount(1, $type->getStatRewards());
        $this->assertTrue($type->getStatRewards()->contains($reward));
        $this->assertSame($type, $reward->getExerciseType());

        $type->removeStatReward($reward);

        $this->assertCount(0, $type->getStatRewards());
    }

    public function testSetterChaining(): void
    {
        $type = new ExerciseType();
        $category = new WorkoutCategory();

        $result = $type->setCategory($category)
            ->setName('Deadlift')
            ->setSlug('deadlift')
            ->setDescription('A compound back exercise');

        $this->assertSame($type, $result);
    }
}
