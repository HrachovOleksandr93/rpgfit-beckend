<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\Gender;
use App\Domain\User\Enum\WorkoutType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class UserEntityTest extends TestCase
{
    public function testUserCreationGeneratesUuid(): void
    {
        $user = new User();

        $this->assertInstanceOf(Uuid::class, $user->getId());
    }

    public function testUserCreationSetsTimestamps(): void
    {
        $user = new User();

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
    }

    public function testUserSettersAndGetters(): void
    {
        $user = new User();

        $user->setLogin('test@example.com');
        $user->setPassword('hashed_password');
        $user->setDisplayName('TestHero');
        $user->setHeight(180.5);
        $user->setWeight(75.0);
        $user->setWorkoutType(WorkoutType::Cardio);
        $user->setActivityLevel(ActivityLevel::Active);
        $user->setDesiredGoal(DesiredGoal::LoseWeight);
        $user->setCharacterRace(CharacterRace::Orc);

        $this->assertSame('test@example.com', $user->getLogin());
        $this->assertSame('hashed_password', $user->getPassword());
        $this->assertSame('TestHero', $user->getDisplayName());
        $this->assertSame(180.5, $user->getHeight());
        $this->assertSame(75.0, $user->getWeight());
        $this->assertSame(WorkoutType::Cardio, $user->getWorkoutType());
        $this->assertSame(ActivityLevel::Active, $user->getActivityLevel());
        $this->assertSame(DesiredGoal::LoseWeight, $user->getDesiredGoal());
        $this->assertSame(CharacterRace::Orc, $user->getCharacterRace());
    }

    public function testNewFieldsSettersAndGetters(): void
    {
        $user = new User();

        // Fields default to null/false
        $this->assertNull($user->getGender());
        $this->assertFalse($user->isOnboardingCompleted());

        // Set fields
        $user->setGender(Gender::Male);
        $user->setOnboardingCompleted(true);

        $this->assertSame(Gender::Male, $user->getGender());
        $this->assertTrue($user->isOnboardingCompleted());
    }

    public function testNullableFieldsDefaultToNull(): void
    {
        $user = new User();

        // Fields that were made nullable for OAuth flow
        $this->assertNull($user->getDisplayName());
        $this->assertNull($user->getHeight());
        $this->assertNull($user->getWeight());
        $this->assertNull($user->getWorkoutType());
        $this->assertNull($user->getActivityLevel());
        $this->assertNull($user->getDesiredGoal());
        $this->assertNull($user->getCharacterRace());
    }

    public function testUserImplementsCorrectInterfaces(): void
    {
        $user = new User();
        $user->setLogin('test@example.com');

        $this->assertInstanceOf(UserInterface::class, $user);
        $this->assertInstanceOf(PasswordAuthenticatedUserInterface::class, $user);
        $this->assertSame('test@example.com', $user->getUserIdentifier());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testEnumAssignments(): void
    {
        $user = new User();

        foreach (WorkoutType::cases() as $type) {
            $user->setWorkoutType($type);
            $this->assertSame($type, $user->getWorkoutType());
        }

        foreach (ActivityLevel::cases() as $level) {
            $user->setActivityLevel($level);
            $this->assertSame($level, $user->getActivityLevel());
        }

        foreach (DesiredGoal::cases() as $goal) {
            $user->setDesiredGoal($goal);
            $this->assertSame($goal, $user->getDesiredGoal());
        }

        foreach (CharacterRace::cases() as $race) {
            $user->setCharacterRace($race);
            $this->assertSame($race, $user->getCharacterRace());
        }

        foreach (Gender::cases() as $gender) {
            $user->setGender($gender);
            $this->assertSame($gender, $user->getGender());
        }
    }

    public function testEnumStringBackedValues(): void
    {
        $this->assertSame('cardio', WorkoutType::Cardio->value);
        $this->assertSame('strength', WorkoutType::Strength->value);
        $this->assertSame('mixed', WorkoutType::Mixed->value);
        $this->assertSame('crossfit', WorkoutType::Crossfit->value);
        $this->assertSame('gymnastics', WorkoutType::Gymnastics->value);
        $this->assertSame('martial_arts', WorkoutType::MartialArts->value);
        $this->assertSame('yoga', WorkoutType::Yoga->value);

        $this->assertSame('sedentary', ActivityLevel::Sedentary->value);
        $this->assertSame('very_active', ActivityLevel::VeryActive->value);

        $this->assertSame('lose_weight', DesiredGoal::LoseWeight->value);
        $this->assertSame('gain_mass', DesiredGoal::GainMass->value);
        $this->assertSame('maintain', DesiredGoal::Maintain->value);

        $this->assertSame('human', CharacterRace::Human->value);
        $this->assertSame('dark_elf', CharacterRace::DarkElf->value);
        $this->assertSame('light_elf', CharacterRace::LightElf->value);
    }

    public function testSetterChaining(): void
    {
        $user = new User();

        $result = $user->setLogin('test@example.com')
            ->setDisplayName('Hero')
            ->setHeight(170.0)
            ->setWeight(65.0)
            ->setWorkoutType(WorkoutType::Mixed)
            ->setActivityLevel(ActivityLevel::Moderate)
            ->setDesiredGoal(DesiredGoal::Maintain)
            ->setCharacterRace(CharacterRace::Human)
            ->setGender(Gender::Female)
            ->setOnboardingCompleted(true);

        $this->assertSame($user, $result);
    }
}
