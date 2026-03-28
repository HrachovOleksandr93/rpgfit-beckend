<?php

declare(strict_types=1);

namespace App\Domain\Health\Enum;

enum HealthDataType: string
{
    case Steps = 'STEPS';
    case HeartRate = 'HEART_RATE';
    case ActiveEnergyBurned = 'ACTIVE_ENERGY_BURNED';
    case DistanceDelta = 'DISTANCE_DELTA';
    case Weight = 'WEIGHT';
    case Height = 'HEIGHT';
    case BodyFatPercentage = 'BODY_FAT_PERCENTAGE';
    case SleepAsleep = 'SLEEP_ASLEEP';
    case SleepDeep = 'SLEEP_DEEP';
    case SleepLight = 'SLEEP_LIGHT';
    case SleepRem = 'SLEEP_REM';
    case Workout = 'WORKOUT';
    case FlightsClimbed = 'FLIGHTS_CLIMBED';
    case BloodOxygen = 'BLOOD_OXYGEN';
    case Water = 'WATER';
}
