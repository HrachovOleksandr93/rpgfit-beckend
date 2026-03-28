<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Media\Entity\MediaFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the MediaFile entity.
 *
 * Validates UUID generation, getters/setters, URL computation,
 * relative path derivation, nullable fields, and the __toString method.
 *
 * Media pipeline context:
 * - storagePath stores only the filename (e.g. "abc123.png")
 * - entityType determines the subdirectory (e.g. "items")
 * - getRelativePath() returns "{entityType}/{filename}" for LiipImagine
 * - getPublicUrl() returns "/uploads/{entityType}/{filename}" for direct access
 */
class MediaFileEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $media = new MediaFile();

        $this->assertInstanceOf(Uuid::class, $media->getId());
    }

    public function testCreationSetsUploadedAt(): void
    {
        $before = new \DateTimeImmutable();
        $media = new MediaFile();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $media->getUploadedAt());
        $this->assertLessThanOrEqual($after, $media->getUploadedAt());
    }

    public function testSettersAndGetters(): void
    {
        $media = new MediaFile();

        $media->setOriginalFilename('sword.png');
        $media->setStoragePath('abc123.png');
        $media->setMimeType('image/png');
        $media->setFileSize(12345);
        $media->setEntityType('items');
        $media->setEntityId('some-uuid-value');

        $this->assertSame('sword.png', $media->getOriginalFilename());
        $this->assertSame('abc123.png', $media->getStoragePath());
        $this->assertSame('image/png', $media->getMimeType());
        $this->assertSame(12345, $media->getFileSize());
        $this->assertSame('items', $media->getEntityType());
        $this->assertSame('some-uuid-value', $media->getEntityId());
    }

    public function testEntityIdIsNullable(): void
    {
        $media = new MediaFile();
        $media->setEntityId(null);

        $this->assertNull($media->getEntityId());
    }

    /** Verify getRelativePath() combines entityType and filename for LiipImagine loader */
    public function testGetRelativePathReturnsEntityTypeSlashFilename(): void
    {
        $media = new MediaFile();
        $media->setEntityType('items');
        $media->setStoragePath('abc123.png');

        $this->assertSame('items/abc123.png', $media->getRelativePath());
    }

    /** Verify getPublicUrl() returns the nginx-served URL for the original file */
    public function testGetPublicUrlReturnsUploadsPath(): void
    {
        $media = new MediaFile();
        $media->setEntityType('items');
        $media->setStoragePath('abc123.png');

        $this->assertSame('/uploads/items/abc123.png', $media->getPublicUrl());
    }

    /** Verify getUrl() still works (deprecated, delegates to getPublicUrl) */
    public function testGetUrlReturnsPublicPath(): void
    {
        $media = new MediaFile();
        $media->setEntityType('skills');
        $media->setStoragePath('def456.jpg');

        $this->assertSame('/uploads/skills/def456.jpg', $media->getUrl());
    }

    /** Verify storagePath stores only filename, not a full relative path */
    public function testStoragePathIsFilenameOnly(): void
    {
        $media = new MediaFile();
        $media->setStoragePath('a1b2c3d4.png');

        // storagePath should NOT contain a slash — it is just the filename
        $this->assertStringNotContainsString('/', $media->getStoragePath());
    }

    public function testToStringReturnsOriginalFilename(): void
    {
        $media = new MediaFile();
        $media->setOriginalFilename('my-image.jpg');

        $this->assertSame('my-image.jpg', (string) $media);
    }

    public function testSetterChaining(): void
    {
        $media = new MediaFile();

        $result = $media
            ->setOriginalFilename('test.png')
            ->setStoragePath('test.png')
            ->setMimeType('image/png')
            ->setFileSize(100)
            ->setEntityType('items')
            ->setEntityId('uuid-here');

        $this->assertSame($media, $result);
    }
}
