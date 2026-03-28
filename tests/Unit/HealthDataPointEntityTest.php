<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Health\Entity\HealthDataPoint;
use App\Domain\Health\Enum\HealthDataType;
use App\Domain\Health\Enum\Platform;
use App\Domain\Health\Enum\RecordingMethod;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class HealthDataPointEntityTest extends TestCase
{
    public function testEntityCreationGeneratesUuid(): void
    {
        $entity = new HealthDataPoint();

        $this->assertInstanceOf(Uuid::class, $entity->getId());
    }

    public function testEntityCreationSetsSyncedAt(): void
    {
        $before = new \DateTimeImmutable();
        $entity = new HealthDataPoint();
        $after = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getSyncedAt());
        $this->assertGreaterThanOrEqual($before, $entity->getSyncedAt());
        $this->assertLessThanOrEqual($after, $entity->getSyncedAt());
    }

    public function testAllFieldGettersAndSetters(): void
    {
        $entity = new HealthDataPoint();
        $user = new User();

        $entity->setUser($user);
        $entity->setExternalUuid('ext-uuid-123');
        $entity->setDataType(HealthDataType::Steps);
        $entity->setValue(8432.0);
        $entity->setUnit('count');
        $entity->setDateFrom(new \DateTimeImmutable('2026-03-28T08:00:00+00:00'));
        $entity->setDateTo(new \DateTimeImmutable('2026-03-28T09:00:00+00:00'));
        $entity->setPlatform(Platform::Ios);
        $entity->setSourceApp('com.apple.health');
        $entity->setRecordingMethod(RecordingMethod::Automatic);

        $this->assertSame($user, $entity->getUser());
        $this->assertSame('ext-uuid-123', $entity->getExternalUuid());
        $this->assertSame(HealthDataType::Steps, $entity->getDataType());
        $this->assertSame(8432.0, $entity->getValue());
        $this->assertSame('count', $entity->getUnit());
        $this->assertEquals(new \DateTimeImmutable('2026-03-28T08:00:00+00:00'), $entity->getDateFrom());
        $this->assertEquals(new \DateTimeImmutable('2026-03-28T09:00:00+00:00'), $entity->getDateTo());
        $this->assertSame(Platform::Ios, $entity->getPlatform());
        $this->assertSame('com.apple.health', $entity->getSourceApp());
        $this->assertSame(RecordingMethod::Automatic, $entity->getRecordingMethod());
    }

    public function testNullableFields(): void
    {
        $entity = new HealthDataPoint();

        $entity->setExternalUuid(null);
        $entity->setSourceApp(null);

        $this->assertNull($entity->getExternalUuid());
        $this->assertNull($entity->getSourceApp());
    }

    public function testEnumAssignments(): void
    {
        $entity = new HealthDataPoint();

        foreach (HealthDataType::cases() as $type) {
            $entity->setDataType($type);
            $this->assertSame($type, $entity->getDataType());
        }

        foreach (Platform::cases() as $platform) {
            $entity->setPlatform($platform);
            $this->assertSame($platform, $entity->getPlatform());
        }

        foreach (RecordingMethod::cases() as $method) {
            $entity->setRecordingMethod($method);
            $this->assertSame($method, $entity->getRecordingMethod());
        }
    }

    public function testSetterChaining(): void
    {
        $entity = new HealthDataPoint();
        $user = new User();

        $result = $entity->setUser($user)
            ->setExternalUuid('uuid-1')
            ->setDataType(HealthDataType::HeartRate)
            ->setValue(72.5)
            ->setUnit('bpm')
            ->setDateFrom(new \DateTimeImmutable())
            ->setDateTo(new \DateTimeImmutable())
            ->setPlatform(Platform::Android)
            ->setSourceApp('Google Fit')
            ->setRecordingMethod(RecordingMethod::Manual);

        $this->assertSame($entity, $result);
    }

    public function testHealthDataTypeEnumValues(): void
    {
        $this->assertSame('STEPS', HealthDataType::Steps->value);
        $this->assertSame('HEART_RATE', HealthDataType::HeartRate->value);
        $this->assertSame('ACTIVE_ENERGY_BURNED', HealthDataType::ActiveEnergyBurned->value);
        $this->assertSame('DISTANCE_DELTA', HealthDataType::DistanceDelta->value);
        $this->assertSame('WEIGHT', HealthDataType::Weight->value);
        $this->assertSame('HEIGHT', HealthDataType::Height->value);
        $this->assertSame('BODY_FAT_PERCENTAGE', HealthDataType::BodyFatPercentage->value);
        $this->assertSame('SLEEP_ASLEEP', HealthDataType::SleepAsleep->value);
        $this->assertSame('SLEEP_DEEP', HealthDataType::SleepDeep->value);
        $this->assertSame('SLEEP_LIGHT', HealthDataType::SleepLight->value);
        $this->assertSame('SLEEP_REM', HealthDataType::SleepRem->value);
        $this->assertSame('WORKOUT', HealthDataType::Workout->value);
        $this->assertSame('FLIGHTS_CLIMBED', HealthDataType::FlightsClimbed->value);
        $this->assertSame('BLOOD_OXYGEN', HealthDataType::BloodOxygen->value);
        $this->assertSame('WATER', HealthDataType::Water->value);
    }

    public function testPlatformEnumValues(): void
    {
        $this->assertSame('ios', Platform::Ios->value);
        $this->assertSame('android', Platform::Android->value);
    }

    public function testRecordingMethodEnumValues(): void
    {
        $this->assertSame('automatic', RecordingMethod::Automatic->value);
        $this->assertSame('manual', RecordingMethod::Manual->value);
    }
}
