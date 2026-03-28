<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Enum\StatType;
use App\Domain\Training\Entity\ExerciseStatReward;
use App\Domain\Training\Entity\ExerciseType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ExerciseStatRewardEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $reward = new ExerciseStatReward();

        $this->assertInstanceOf(Uuid::class, $reward->getId());
    }

    public function testSettersAndGetters(): void
    {
        $reward = new ExerciseStatReward();
        $exerciseType = new ExerciseType();

        $reward->setExerciseType($exerciseType);
        $reward->setStatType(StatType::Strength);
        $reward->setPoints(5);

        $this->assertSame($exerciseType, $reward->getExerciseType());
        $this->assertSame(StatType::Strength, $reward->getStatType());
        $this->assertSame(5, $reward->getPoints());
    }

    public function testAllStatTypes(): void
    {
        $reward = new ExerciseStatReward();

        foreach (StatType::cases() as $statType) {
            $reward->setStatType($statType);
            $this->assertSame($statType, $reward->getStatType());
        }
    }

    public function testSetterChaining(): void
    {
        $reward = new ExerciseStatReward();
        $exerciseType = new ExerciseType();

        $result = $reward->setExerciseType($exerciseType)
            ->setStatType(StatType::Dexterity)
            ->setPoints(3);

        $this->assertSame($reward, $result);
    }
}
