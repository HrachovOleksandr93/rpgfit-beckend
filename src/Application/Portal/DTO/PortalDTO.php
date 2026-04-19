<?php

declare(strict_types=1);

namespace App\Application\Portal\DTO;

use App\Domain\Portal\Entity\Portal;

/**
 * Read-only DTO used for API responses. Independent of Doctrine so the API
 * layer does not leak entity internals or lazy-load collections.
 */
final class PortalDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $type,
        public readonly string $realm,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $radiusM,
        public readonly int $tier,
        public readonly ?string $challengeType,
        public readonly array $challengeParams,
        public readonly ?string $rewardArtifactSlug,
        public readonly ?string $virtualReplicaOfId,
        public readonly ?string $createdByUserId,
        public readonly ?string $expiresAt,
        public readonly ?int $maxBattles,
        public readonly ?float $distanceKm = null,
    ) {
    }

    public static function fromEntity(Portal $portal, ?float $distanceKm = null): self
    {
        return new self(
            id: $portal->getId()->toRfc4122(),
            slug: $portal->getSlug(),
            name: $portal->getName(),
            type: $portal->getType()->value,
            realm: $portal->getRealm()->value,
            latitude: $portal->getLatitude(),
            longitude: $portal->getLongitude(),
            radiusM: $portal->getRadiusM(),
            tier: $portal->getTier(),
            challengeType: $portal->getChallengeType(),
            challengeParams: $portal->getChallengeParams(),
            rewardArtifactSlug: $portal->getRewardArtifactSlug(),
            virtualReplicaOfId: $portal->getVirtualReplicaOf()?->getId()->toRfc4122(),
            createdByUserId: $portal->getCreatedByUser()?->getId()->toRfc4122(),
            expiresAt: $portal->getExpiresAt()?->format(\DateTimeInterface::ATOM),
            maxBattles: $portal->getMaxBattles(),
            distanceKm: $distanceKm,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'realm' => $this->realm,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_m' => $this->radiusM,
            'tier' => $this->tier,
            'challenge_type' => $this->challengeType,
            'challenge_params' => $this->challengeParams,
            'reward_artifact_slug' => $this->rewardArtifactSlug,
            'virtual_replica_of_id' => $this->virtualReplicaOfId,
            'created_by_user_id' => $this->createdByUserId,
            'expires_at' => $this->expiresAt,
            'max_battles' => $this->maxBattles,
            'distance_km' => $this->distanceKm,
        ];
    }
}
