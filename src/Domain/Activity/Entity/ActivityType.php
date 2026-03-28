<?php

declare(strict_types=1);

namespace App\Domain\Activity\Entity;

use App\Infrastructure\Activity\Repository\ActivityTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Represents a single health-app activity type (99 total from Flutter health package).
 *
 * Domain layer (Activity bounded context). Maps Flutter enum values to native iOS
 * (HealthKit) and Android (Health Connect) type identifiers. Platform-specific types
 * include a fallback slug pointing to the closest equivalent on the other platform.
 */
#[ORM\Entity(repositoryClass: ActivityTypeRepository::class)]
#[ORM\Table(name: 'activity_types')]
#[ORM\Index(name: 'idx_activity_type_category', columns: ['category_id'])]
class ActivityType
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** Unique slug identifier, e.g. "running", "boxing" */
    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $slug;

    /** Human-readable display name */
    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    /** Exact Flutter HealthWorkoutActivityType enum value, e.g. "RUNNING" */
    #[ORM\Column(type: 'string', length: 100)]
    private string $flutterEnum;

    /** Apple HealthKit HKWorkoutActivityType case name, null if Android-only */
    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $iosNative = null;

    /** Android Health Connect ExerciseSessionRecord type, null if iOS-only */
    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $androidNative = null;

    /** Platform support: 'universal', 'ios_only', or 'android_only' */
    #[ORM\Column(type: 'string', length: 20)]
    private string $platformSupport;

    /** Slug of closest equivalent activity on the other platform, null for universal */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $fallbackSlug = null;

    /** Category this activity type belongs to */
    #[ORM\ManyToOne(targetEntity: ActivityCategory::class, inversedBy: 'activityTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ActivityCategory $category;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFlutterEnum(): string
    {
        return $this->flutterEnum;
    }

    public function setFlutterEnum(string $flutterEnum): self
    {
        $this->flutterEnum = $flutterEnum;

        return $this;
    }

    public function getIosNative(): ?string
    {
        return $this->iosNative;
    }

    public function setIosNative(?string $iosNative): self
    {
        $this->iosNative = $iosNative;

        return $this;
    }

    public function getAndroidNative(): ?string
    {
        return $this->androidNative;
    }

    public function setAndroidNative(?string $androidNative): self
    {
        $this->androidNative = $androidNative;

        return $this;
    }

    public function getPlatformSupport(): string
    {
        return $this->platformSupport;
    }

    public function setPlatformSupport(string $platformSupport): self
    {
        $this->platformSupport = $platformSupport;

        return $this;
    }

    public function getFallbackSlug(): ?string
    {
        return $this->fallbackSlug;
    }

    public function setFallbackSlug(?string $fallbackSlug): self
    {
        $this->fallbackSlug = $fallbackSlug;

        return $this;
    }

    public function getCategory(): ActivityCategory
    {
        return $this->category;
    }

    public function setCategory(ActivityCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
