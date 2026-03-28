<?php

declare(strict_types=1);

namespace App\Domain\Activity\Entity;

use App\Domain\Character\Enum\StatType;
use App\Domain\Media\Entity\MediaFile;
use App\Infrastructure\Activity\Repository\ProfessionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Represents an RPG profession within a category (48 total: 3 tiers x 16 categories).
 *
 * Domain layer (Activity bounded context). Each profession belongs to a category
 * and has a tier (1 = starter, 2 = intermediate, 3 = master). Professions define
 * which stats are primarily and secondarily boosted by training in that category.
 */
#[ORM\Entity(repositoryClass: ProfessionRepository::class)]
#[ORM\Table(name: 'professions')]
#[ORM\Index(name: 'idx_profession_category_tier', columns: ['category_id', 'tier'])]
class Profession
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** Display name of the profession, e.g. "Gladiator", "Wind Rider" */
    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    /** Unique slug identifier, e.g. "gladiator", "wind-rider" */
    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $slug;

    /** Profession tier: 1 (starter), 2 (intermediate), 3 (master) */
    #[ORM\Column(type: 'integer')]
    private int $tier;

    /** RPG-flavored description of the profession */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** Primary stat boosted by this profession */
    #[ORM\Column(type: 'string', length: 20, enumType: StatType::class)]
    private StatType $primaryStat;

    /** Secondary stat boosted by this profession */
    #[ORM\Column(type: 'string', length: 20, enumType: StatType::class)]
    private StatType $secondaryStat;

    /** Category this profession belongs to */
    #[ORM\ManyToOne(targetEntity: ActivityCategory::class, inversedBy: 'professions')]
    #[ORM\JoinColumn(nullable: false)]
    private ActivityCategory $category;

    /** Optional image for the profession */
    #[ORM\ManyToOne(targetEntity: MediaFile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?MediaFile $image = null;

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

    public function getTier(): int
    {
        return $this->tier;
    }

    public function setTier(int $tier): self
    {
        $this->tier = $tier;

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

    public function getPrimaryStat(): StatType
    {
        return $this->primaryStat;
    }

    public function setPrimaryStat(StatType $primaryStat): self
    {
        $this->primaryStat = $primaryStat;

        return $this;
    }

    public function getSecondaryStat(): StatType
    {
        return $this->secondaryStat;
    }

    public function setSecondaryStat(StatType $secondaryStat): self
    {
        $this->secondaryStat = $secondaryStat;

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

    public function getImage(): ?MediaFile
    {
        return $this->image;
    }

    public function setImage(?MediaFile $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
