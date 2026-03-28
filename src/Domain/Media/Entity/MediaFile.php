<?php

declare(strict_types=1);

namespace App\Domain\Media\Entity;

use App\Infrastructure\Media\Repository\MediaFileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Tracks uploaded media files (images) associated with game entities.
 *
 * Domain layer (Media bounded context). Each row represents one uploaded file
 * stored on disk. The entityType/entityId fields create a polymorphic link to
 * any game entity (item, skill, character, mob) without hard foreign keys.
 *
 * Media pipeline:
 * 1. Upload via API -> file stored in public/uploads/{entityType}/{filename}
 * 2. Original served directly by nginx from /uploads/
 * 3. Resized variants served by LiipImagineBundle from /media/cache/
 *
 * The storagePath field stores only the filename (e.g. "a1b2c3d4.png").
 * The entityType field determines the subdirectory (items/, skills/, etc.).
 * Full relative path is derived via getRelativePath(): "{entityType}/{filename}".
 */
#[ORM\Entity(repositoryClass: MediaFileRepository::class)]
#[ORM\Table(name: 'media_files')]
#[ORM\Index(name: 'idx_media_entity', columns: ['entity_type', 'entity_id'])]
class MediaFile
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** Original name of the uploaded file (e.g. "sword-of-fire.png") */
    #[ORM\Column(type: 'string', length: 255)]
    private string $originalFilename;

    /** Filename only on disk (e.g. "a1b2c3d4.png"), without directory */
    #[ORM\Column(type: 'string', length: 500)]
    private string $storagePath;

    /** MIME type of the uploaded file (e.g. "image/png") */
    #[ORM\Column(type: 'string', length: 50)]
    private string $mimeType;

    /** File size in bytes */
    #[ORM\Column(type: 'integer')]
    private int $fileSize;

    /** What kind of entity this image belongs to: 'items', 'skills', 'characters', 'mobs' */
    #[ORM\Column(type: 'string', length: 50)]
    private string $entityType;

    /** UUID of the related entity (polymorphic, nullable for unlinked uploads) */
    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $entityId = null;

    /** When the file was uploaded */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $uploadedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): self
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function setStoragePath(string $storagePath): self
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): self
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setEntityId(?string $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getUploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    /**
     * Returns the relative path used by LiipImagine loader: "{entityType}/{filename}".
     *
     * This path is relative to the uploads root (public/uploads/).
     */
    public function getRelativePath(): string
    {
        return $this->entityType . '/' . $this->storagePath;
    }

    /**
     * Returns the public URL path for the original file (relative to web root).
     *
     * The original file is served directly by nginx from /uploads/{entityType}/{filename}.
     */
    public function getPublicUrl(): string
    {
        return '/uploads/' . $this->entityType . '/' . $this->storagePath;
    }

    /**
     * Returns the public URL path for this file.
     *
     * @deprecated Use getPublicUrl() instead for clarity
     */
    public function getUrl(): string
    {
        return $this->getPublicUrl();
    }

    public function __toString(): string
    {
        return $this->originalFilename;
    }
}
