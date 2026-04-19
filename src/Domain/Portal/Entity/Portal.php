<?php

declare(strict_types=1);

namespace App\Domain\Portal\Entity;

use App\Domain\Portal\Enum\PortalType;
use App\Domain\Shared\Enum\Realm;
use App\Domain\User\Entity\User;
use App\Infrastructure\Portal\Repository\PortalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A Rupture portal on the world map.
 *
 * Domain layer (Portal bounded context). Portals are the geo-bound entry
 * points for Battles. Three flavours exist (see PortalType):
 *  - static:       curated landmarks (mountains, temples, stadiums)
 *  - dynamic:      spawn-and-expire algorithmic portals
 *  - user_created: placed by a user after consuming a PortalCreationKit item
 *
 * Each portal is bound to exactly one Realm, which drives mob spawn filtering
 * and the artifact realm-match damage multiplier (BUSINESS_LOGIC §12).
 *
 * Geometry fields use plain floats (no PostGIS): we keep MySQL 8 and use
 * either `ST_Distance_Sphere` (native) or Haversine in the repository.
 */
#[ORM\Entity(repositoryClass: PortalRepository::class)]
#[ORM\Table(name: 'portals')]
#[ORM\Index(name: 'idx_portal_latlng', columns: ['latitude', 'longitude'])]
#[ORM\Index(name: 'idx_portal_type_realm', columns: ['type', 'realm'])]
#[ORM\Index(name: 'idx_portal_expires_at', columns: ['expires_at'])]
class Portal
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'string', length: 140, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 20, enumType: PortalType::class)]
    private PortalType $type;

    #[ORM\Column(type: 'string', length: 20, enumType: Realm::class)]
    private Realm $realm;

    #[ORM\Column(type: 'float')]
    private float $latitude;

    #[ORM\Column(type: 'float')]
    private float $longitude;

    /** Effective radius in meters (for geofencing / trigger zone). */
    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    private int $radiusM = 100;

    /** Mob tier target (1..3), drives default spawn filtering. */
    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $tier = 1;

    /** Challenge type slug — e.g. `cardio_distance`, `strength_volume`, `yoga_minutes`. */
    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    private ?string $challengeType = null;

    /**
     * Free-form challenge parameters for the challenge type (e.g. target_km, target_minutes).
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $challengeParams = [];

    /** Slug of the artifact awarded on portal completion (nullable). */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $rewardArtifactSlug = null;

    /**
     * Optional self-FK: a "virtual" replica of another (usually static) portal,
     * used to let players far from the physical location attempt the challenge.
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'virtual_replica_of_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Portal $virtualReplicaOf = null;

    /** The user who created this portal (nullable for static / admin-curated ones). */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdByUser = null;

    /** Expiration timestamp for dynamic / user-created portals (null for static). */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    /** Maximum number of successful battles this portal supports (null = unlimited). */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxBattles = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getType(): PortalType
    {
        return $this->type;
    }

    public function setType(PortalType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRealm(): Realm
    {
        return $this->realm;
    }

    public function setRealm(Realm $realm): self
    {
        $this->realm = $realm;

        return $this;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getRadiusM(): int
    {
        return $this->radiusM;
    }

    public function setRadiusM(int $radiusM): self
    {
        $this->radiusM = $radiusM;

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

    public function getChallengeType(): ?string
    {
        return $this->challengeType;
    }

    public function setChallengeType(?string $challengeType): self
    {
        $this->challengeType = $challengeType;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getChallengeParams(): array
    {
        return $this->challengeParams;
    }

    /** @param array<string, mixed> $challengeParams */
    public function setChallengeParams(array $challengeParams): self
    {
        $this->challengeParams = $challengeParams;

        return $this;
    }

    public function getRewardArtifactSlug(): ?string
    {
        return $this->rewardArtifactSlug;
    }

    public function setRewardArtifactSlug(?string $rewardArtifactSlug): self
    {
        $this->rewardArtifactSlug = $rewardArtifactSlug;

        return $this;
    }

    public function getVirtualReplicaOf(): ?Portal
    {
        return $this->virtualReplicaOf;
    }

    public function setVirtualReplicaOf(?Portal $virtualReplicaOf): self
    {
        $this->virtualReplicaOf = $virtualReplicaOf;

        return $this;
    }

    public function getCreatedByUser(): ?User
    {
        return $this->createdByUser;
    }

    public function setCreatedByUser(?User $createdByUser): self
    {
        $this->createdByUser = $createdByUser;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getMaxBattles(): ?int
    {
        return $this->maxBattles;
    }

    public function setMaxBattles(?int $maxBattles): self
    {
        $this->maxBattles = $maxBattles;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
