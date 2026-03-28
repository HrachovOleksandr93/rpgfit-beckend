<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Repository;

use App\Domain\Media\Entity\MediaFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for MediaFile entities.
 *
 * Infrastructure layer (Media bounded context). Provides data access for uploaded
 * media file records. Supports querying by polymorphic entity reference.
 *
 * @extends ServiceEntityRepository<MediaFile>
 */
class MediaFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaFile::class);
    }

    /** Persist a MediaFile entity to the database. */
    public function save(MediaFile $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Find all media files linked to a specific entity.
     *
     * @return MediaFile[]
     */
    public function findByEntity(string $entityType, string $entityId): array
    {
        return $this->findBy([
            'entityType' => $entityType,
            'entityId' => $entityId,
        ]);
    }
}
