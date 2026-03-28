<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\User\DTO\RegistrationDTO;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationDTOTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    private function createValidDTO(): RegistrationDTO
    {
        $dto = new RegistrationDTO();
        $dto->login = 'user@example.com';
        $dto->password = 'securePassword123';
        $dto->displayName = 'HeroName';
        $dto->height = 175.0;
        $dto->weight = 70.0;
        $dto->workoutType = WorkoutType::Cardio;
        $dto->activityLevel = ActivityLevel::Active;
        $dto->desiredGoal = DesiredGoal::LoseWeight;
        $dto->characterRace = CharacterRace::Human;

        return $dto;
    }

    public function testValidDTOHasNoViolations(): void
    {
        $dto = $this->createValidDTO();
        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    public function testBlankLoginIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->login = '';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testInvalidEmailIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->login = 'not-an-email';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testShortPasswordIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->password = 'short';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testBlankPasswordIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->password = '';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDisplayNameTooShortIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->displayName = 'ab';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDisplayNameTooLongIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->displayName = str_repeat('a', 31);

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testNullHeightIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->height = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testNegativeHeightIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->height = -10.0;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testNullWeightIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->weight = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testNegativeWeightIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->weight = -5.0;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testNullWorkoutTypeIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->workoutType = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testNullActivityLevelIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->activityLevel = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testNullDesiredGoalIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->desiredGoal = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testNullCharacterRaceIsInvalid(): void
    {
        $dto = $this->createValidDTO();
        $dto->characterRace = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }
}
