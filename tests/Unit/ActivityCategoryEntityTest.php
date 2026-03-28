<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Activity\Entity\ActivityCategory;
use App\Domain\Activity\Entity\ActivityType;
use App\Domain\Activity\Entity\Profession;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the ActivityCategory entity.
 *
 * Verifies UUID generation, getter/setter behaviour, setter chaining,
 * nullable fields, collection management, and __toString output.
 */
class ActivityCategoryEntityTest extends TestCase
{
    /** Verify that a new ActivityCategory automatically receives a UUID v4 identifier. */
    public function testCreationGeneratesUuid(): void
    {
        $category = new ActivityCategory();

        $this->assertInstanceOf(Uuid::class, $category->getId());
    }

    /** Verify all getters return the values set by their corresponding setters. */
    public function testSettersAndGetters(): void
    {
        $category = new ActivityCategory();

        $category->setSlug('combat');
        $category->setName('Combat');
        $category->setDescription('Close-quarters battle.');

        $this->assertSame('combat', $category->getSlug());
        $this->assertSame('Combat', $category->getName());
        $this->assertSame('Close-quarters battle.', $category->getDescription());
    }

    /** Verify that all setters return $this to support fluent chaining. */
    public function testSetterChaining(): void
    {
        $category = new ActivityCategory();

        $result = $category->setSlug('running')
            ->setName('Running')
            ->setDescription('Fleet-footed scouts.');

        $this->assertSame($category, $result);
    }

    /** Verify that nullable fields accept null values. */
    public function testNullableFields(): void
    {
        $category = new ActivityCategory();

        $category->setDescription(null);

        $this->assertNull($category->getDescription());
    }

    /** Verify that professions collection starts empty and accepts additions. */
    public function testProfessionsCollection(): void
    {
        $category = new ActivityCategory();
        $category->setName('Combat')->setSlug('combat');

        $this->assertCount(0, $category->getProfessions());

        $profession = new Profession();
        $profession->setName('Fighter')->setSlug('fighter');
        $category->addProfession($profession);

        $this->assertCount(1, $category->getProfessions());
        $this->assertSame($category, $profession->getCategory());
    }

    /** Verify that activity types collection starts empty and accepts additions. */
    public function testActivityTypesCollection(): void
    {
        $category = new ActivityCategory();
        $category->setName('Combat')->setSlug('combat');

        $this->assertCount(0, $category->getActivityTypes());

        $activityType = new ActivityType();
        $activityType->setName('Boxing')->setSlug('boxing');
        $category->addActivityType($activityType);

        $this->assertCount(1, $category->getActivityTypes());
        $this->assertSame($category, $activityType->getCategory());
    }

    /** Verify that adding the same entity twice does not create duplicates. */
    public function testNoDuplicatesInCollections(): void
    {
        $category = new ActivityCategory();
        $category->setName('Combat')->setSlug('combat');

        $profession = new Profession();
        $profession->setName('Fighter')->setSlug('fighter');

        $category->addProfession($profession);
        $category->addProfession($profession);

        $this->assertCount(1, $category->getProfessions());
    }

    /** Verify that __toString returns the category name. */
    public function testToStringReturnsName(): void
    {
        $category = new ActivityCategory();
        $category->setName('Swimming');

        $this->assertSame('Swimming', (string) $category);
    }
}
