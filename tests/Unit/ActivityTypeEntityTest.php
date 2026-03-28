<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Activity\Entity\ActivityCategory;
use App\Domain\Activity\Entity\ActivityType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the ActivityType entity.
 *
 * Verifies UUID generation, getter/setter behaviour, setter chaining,
 * nullable fields, platform support values, and __toString output.
 */
class ActivityTypeEntityTest extends TestCase
{
    /** Verify that a new ActivityType automatically receives a UUID v4 identifier. */
    public function testCreationGeneratesUuid(): void
    {
        $activityType = new ActivityType();

        $this->assertInstanceOf(Uuid::class, $activityType->getId());
    }

    /** Verify all getters return the values set by their corresponding setters. */
    public function testSettersAndGetters(): void
    {
        $activityType = new ActivityType();
        $category = new ActivityCategory();
        $category->setName('Combat')->setSlug('combat');

        $activityType->setSlug('boxing');
        $activityType->setName('Boxing');
        $activityType->setFlutterEnum('BOXING');
        $activityType->setIosNative('boxing');
        $activityType->setAndroidNative('EXERCISE_TYPE_BOXING');
        $activityType->setPlatformSupport('universal');
        $activityType->setFallbackSlug(null);
        $activityType->setCategory($category);

        $this->assertSame('boxing', $activityType->getSlug());
        $this->assertSame('Boxing', $activityType->getName());
        $this->assertSame('BOXING', $activityType->getFlutterEnum());
        $this->assertSame('boxing', $activityType->getIosNative());
        $this->assertSame('EXERCISE_TYPE_BOXING', $activityType->getAndroidNative());
        $this->assertSame('universal', $activityType->getPlatformSupport());
        $this->assertNull($activityType->getFallbackSlug());
        $this->assertSame($category, $activityType->getCategory());
    }

    /** Verify that all setters return $this to support fluent chaining. */
    public function testSetterChaining(): void
    {
        $activityType = new ActivityType();
        $category = new ActivityCategory();
        $category->setName('Combat')->setSlug('combat');

        $result = $activityType->setSlug('boxing')
            ->setName('Boxing')
            ->setFlutterEnum('BOXING')
            ->setIosNative('boxing')
            ->setAndroidNative('EXERCISE_TYPE_BOXING')
            ->setPlatformSupport('universal')
            ->setFallbackSlug(null)
            ->setCategory($category);

        $this->assertSame($activityType, $result);
    }

    /** Verify that nullable fields accept null values. */
    public function testNullableFields(): void
    {
        $activityType = new ActivityType();

        $activityType->setIosNative(null);
        $activityType->setAndroidNative(null);
        $activityType->setFallbackSlug(null);

        $this->assertNull($activityType->getIosNative());
        $this->assertNull($activityType->getAndroidNative());
        $this->assertNull($activityType->getFallbackSlug());
    }

    /** Verify iOS-only activity type setup (no androidNative, has fallback). */
    public function testIosOnlyPlatformSetup(): void
    {
        $activityType = new ActivityType();

        $activityType->setPlatformSupport('ios_only');
        $activityType->setIosNative('barre');
        $activityType->setAndroidNative(null);
        $activityType->setFallbackSlug('pilates');

        $this->assertSame('ios_only', $activityType->getPlatformSupport());
        $this->assertSame('barre', $activityType->getIosNative());
        $this->assertNull($activityType->getAndroidNative());
        $this->assertSame('pilates', $activityType->getFallbackSlug());
    }

    /** Verify Android-only activity type setup (no iosNative, has fallback). */
    public function testAndroidOnlyPlatformSetup(): void
    {
        $activityType = new ActivityType();

        $activityType->setPlatformSupport('android_only');
        $activityType->setIosNative(null);
        $activityType->setAndroidNative('EXERCISE_TYPE_BIKING_STATIONARY');
        $activityType->setFallbackSlug('biking');

        $this->assertSame('android_only', $activityType->getPlatformSupport());
        $this->assertNull($activityType->getIosNative());
        $this->assertSame('EXERCISE_TYPE_BIKING_STATIONARY', $activityType->getAndroidNative());
        $this->assertSame('biking', $activityType->getFallbackSlug());
    }

    /** Verify that __toString returns the activity type name. */
    public function testToStringReturnsName(): void
    {
        $activityType = new ActivityType();
        $activityType->setName('Running');

        $this->assertSame('Running', (string) $activityType);
    }
}
