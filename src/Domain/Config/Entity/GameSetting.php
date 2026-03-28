<?php

declare(strict_types=1);

namespace App\Domain\Config\Entity;

use App\Infrastructure\Config\Repository\GameSettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Stores a single game configuration setting as a key-value pair.
 *
 * Domain layer (Config bounded context). Settings are grouped by category
 * (e.g. 'xp_rates', 'xp_caps', 'leveling', 'bonuses') and identified by a unique key.
 * Values are stored as strings and cast to the appropriate type in the consuming service.
 *
 * This table is the single source of truth for all tunable game parameters:
 * XP rates per health data type, daily caps, leveling curve coefficients, and streak bonuses.
 * Editable via Sonata Admin so designers can tweak the economy without code changes.
 */
#[ORM\Entity(repositoryClass: GameSettingRepository::class)]
#[ORM\Table(name: 'game_settings')]
#[ORM\Index(name: 'idx_game_setting_category', columns: ['category'])]
class GameSetting
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** Grouping label: 'xp_rates', 'xp_caps', 'leveling', 'bonuses'. */
    #[ORM\Column(type: 'string', length: 50)]
    private string $category;

    /** Unique machine-readable setting name, e.g. 'xp_rate_steps'. */
    #[ORM\Column(name: '`key`', type: 'string', length: 100, unique: true)]
    private string $key;

    /** Raw string value (cast to int/float by the consuming service). */
    #[ORM\Column(type: 'string', length: 255)]
    private string $value;

    /** Human-readable explanation of what this setting controls. */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

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
        return $this->key;
    }
}
