<?php

declare(strict_types=1);

namespace App\Domain\Workout\Entity;

use App\Domain\Workout\Enum\SplitType;
use App\Infrastructure\Workout\Repository\SplitTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Pre-defined training split template that organizes muscle groups across weekly days.
 *
 * Domain layer (Workout bounded context). Each template defines a weekly training
 * structure (e.g. Push/Pull/Legs, Upper/Lower) with day-by-day muscle group
 * assignments stored in dayConfigs JSON.
 *
 * Used by the workout plan generator to determine which muscles to train on each day.
 */
#[ORM\Entity(repositoryClass: SplitTemplateRepository::class)]
#[ORM\Table(name: 'split_templates')]
class SplitTemplate
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** Human-readable name, e.g. "Push/Pull/Legs" */
    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 20, enumType: SplitType::class)]
    private SplitType $splitType;

    /** Number of training days per week (2-6) */
    #[ORM\Column(type: 'integer')]
    private int $daysPerWeek;

    /**
     * JSON array of day configurations.
     * Each entry: {"day": 1, "name": "Push", "muscleGroups": ["chest", "shoulders", "triceps"]}
     */
    #[ORM\Column(type: 'json')]
    private array $dayConfigs = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSplitType(): SplitType
    {
        return $this->splitType;
    }

    public function setSplitType(SplitType $splitType): self
    {
        $this->splitType = $splitType;

        return $this;
    }

    public function getDaysPerWeek(): int
    {
        return $this->daysPerWeek;
    }

    public function setDaysPerWeek(int $daysPerWeek): self
    {
        $this->daysPerWeek = $daysPerWeek;

        return $this;
    }

    public function getDayConfigs(): array
    {
        return $this->dayConfigs;
    }

    public function setDayConfigs(array $dayConfigs): self
    {
        $this->dayConfigs = $dayConfigs;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
