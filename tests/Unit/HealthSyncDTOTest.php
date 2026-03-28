<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Health\DTO\HealthDataPointDTO;
use App\Application\Health\DTO\HealthSyncDTO;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HealthSyncDTOTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    private function createValidDataPointDTO(): HealthDataPointDTO
    {
        $dto = new HealthDataPointDTO();
        $dto->externalUuid = 'ext-uuid-123';
        $dto->type = 'STEPS';
        $dto->value = 8432.0;
        $dto->unit = 'count';
        $dto->dateFrom = '2026-03-28T08:00:00+00:00';
        $dto->dateTo = '2026-03-28T09:00:00+00:00';
        $dto->sourceApp = 'com.apple.health';
        $dto->recordingMethod = 'automatic';

        return $dto;
    }

    private function createValidSyncDTO(): HealthSyncDTO
    {
        $dto = new HealthSyncDTO();
        $dto->platform = 'ios';
        $dto->dataPoints = [$this->createValidDataPointDTO()];

        return $dto;
    }

    public function testValidDTOHasNoViolations(): void
    {
        $dto = $this->createValidSyncDTO();
        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    public function testMissingPlatformIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->platform = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testEmptyDataPointsIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints = [];

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDataPointMissingTypeIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->type = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDataPointNullValueIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->value = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDataPointNegativeValueIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->value = -1.0;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDataPointZeroValueIsValid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->value = 0.0;

        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testDataPointMissingUnitIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->unit = '';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDataPointMissingDateFromIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->dateFrom = '';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDataPointMissingDateToIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->dateTo = '';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDataPointInvalidDateFormatIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->dateFrom = 'not-a-date';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDataPointMissingRecordingMethodIsInvalid(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->recordingMethod = null;

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testDataPointNullableFieldsAreOptional(): void
    {
        $dto = $this->createValidSyncDTO();
        $dto->dataPoints[0]->externalUuid = null;
        $dto->dataPoints[0]->sourceApp = null;

        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }
}
