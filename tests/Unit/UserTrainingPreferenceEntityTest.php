<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Entity\User;
use App\Domain\User\Entity\UserTrainingPreference;
use App\Domain\User\Enum\Lifestyle;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\User\Enum\WorkoutType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UserTrainingPreferenceEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $pref = new UserTrainingPreference();

        $this->assertInstanceOf(Uuid::class, $pref->getId());
    }

    public function testNullableFieldsDefaultToNull(): void
    {
        $pref = new UserTrainingPreference();

        $this->assertNull($pref->getTrainingFrequency());
        $this->assertNull($pref->getLifestyle());
        $this->assertNull($pref->getPrimaryTrainingStyle());
        $this->assertNull($pref->getPreferredWorkouts());
    }

    public function testSettersAndGetters(): void
    {
        $pref = new UserTrainingPreference();
        $user = new User();
        $user->setLogin('test@rpgfit.com');
        $user->setPassword('hashed');

        $pref->setUser($user);
        $pref->setTrainingFrequency(TrainingFrequency::Moderate);
        $pref->setLifestyle(Lifestyle::Active);
        $pref->setPrimaryTrainingStyle(WorkoutType::Strength);
        $pref->setPreferredWorkouts(['powerlifting', 'crossfit']);

        $this->assertSame($user, $pref->getUser());
        $this->assertSame(TrainingFrequency::Moderate, $pref->getTrainingFrequency());
        $this->assertSame(Lifestyle::Active, $pref->getLifestyle());
        $this->assertSame(WorkoutType::Strength, $pref->getPrimaryTrainingStyle());
        $this->assertSame(['powerlifting', 'crossfit'], $pref->getPreferredWorkouts());
    }

    public function testSetterChaining(): void
    {
        $pref = new UserTrainingPreference();
        $user = new User();
        $user->setLogin('chain@rpgfit.com');
        $user->setPassword('hashed');

        $result = $pref
            ->setUser($user)
            ->setTrainingFrequency(TrainingFrequency::Heavy)
            ->setLifestyle(Lifestyle::VeryActive)
            ->setPrimaryTrainingStyle(WorkoutType::Crossfit)
            ->setPreferredWorkouts(['running']);

        $this->assertSame($pref, $result);
    }

    public function testAllEnumValues(): void
    {
        $pref = new UserTrainingPreference();

        foreach (TrainingFrequency::cases() as $freq) {
            $pref->setTrainingFrequency($freq);
            $this->assertSame($freq, $pref->getTrainingFrequency());
        }

        foreach (Lifestyle::cases() as $ls) {
            $pref->setLifestyle($ls);
            $this->assertSame($ls, $pref->getLifestyle());
        }

        foreach (WorkoutType::cases() as $type) {
            $pref->setPrimaryTrainingStyle($type);
            $this->assertSame($type, $pref->getPrimaryTrainingStyle());
        }
    }

    public function testToStringMethod(): void
    {
        $pref = new UserTrainingPreference();
        $user = new User();
        $user->setLogin('tostring@rpgfit.com');
        $user->setPassword('hashed');
        $user->setDisplayName('TestHero');

        $pref->setUser($user);

        $this->assertSame('Training Preferences for TestHero', (string) $pref);
    }
}
