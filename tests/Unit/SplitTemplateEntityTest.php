<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Workout\Entity\SplitTemplate;
use App\Domain\Workout\Enum\SplitType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the SplitTemplate entity.
 *
 * Verifies UUID generation, getter/setter contracts, nullable fields,
 * JSON day configs handling, and setter chaining.
 */
class SplitTemplateEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $template = new SplitTemplate();

        $this->assertInstanceOf(Uuid::class, $template->getId());
    }

    public function testSettersAndGetters(): void
    {
        $template = new SplitTemplate();
        $dayConfigs = [
            ['day' => 1, 'name' => 'Push', 'muscleGroups' => ['chest', 'shoulders', 'triceps']],
            ['day' => 2, 'name' => 'Pull', 'muscleGroups' => ['back', 'biceps']],
            ['day' => 3, 'name' => 'Legs', 'muscleGroups' => ['quads', 'hamstrings', 'glutes', 'calves']],
        ];

        $template->setName('Push/Pull/Legs')
            ->setSlug('push-pull-legs')
            ->setSplitType(SplitType::PushPullLegs)
            ->setDaysPerWeek(3)
            ->setDayConfigs($dayConfigs)
            ->setDescription('Classic three-day split.');

        $this->assertSame('Push/Pull/Legs', $template->getName());
        $this->assertSame('push-pull-legs', $template->getSlug());
        $this->assertSame(SplitType::PushPullLegs, $template->getSplitType());
        $this->assertSame(3, $template->getDaysPerWeek());
        $this->assertSame($dayConfigs, $template->getDayConfigs());
        $this->assertSame('Classic three-day split.', $template->getDescription());
    }

    public function testDefaultDayConfigs(): void
    {
        $template = new SplitTemplate();

        $this->assertSame([], $template->getDayConfigs());
    }

    public function testNullableDescription(): void
    {
        $template = new SplitTemplate();

        $template->setDescription(null);

        $this->assertNull($template->getDescription());
    }

    public function testSetterChaining(): void
    {
        $template = new SplitTemplate();

        $result = $template->setName('Test')
            ->setSlug('test')
            ->setSplitType(SplitType::FullBody)
            ->setDaysPerWeek(2)
            ->setDayConfigs([])
            ->setDescription(null);

        $this->assertSame($template, $result);
    }

    public function testToString(): void
    {
        $template = new SplitTemplate();
        $template->setName('Upper/Lower');

        $this->assertSame('Upper/Lower', (string) $template);
    }
}
