<?php

declare(strict_types=1);

namespace App\Application\Media\Service;

use App\Domain\Media\Entity\MediaFile;
use App\Infrastructure\Media\Repository\MediaFileRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * Handles file upload processing for media images.
 *
 * Application layer (Media bounded context). Validates MIME types and entity types,
 * generates unique filenames, moves files to the correct subdirectory, and persists
 * the MediaFile entity with metadata.
 *
 * Media pipeline:
 * 1. Validate MIME type and entityType against allowed lists
 * 2. Generate unique filename: {uuid}.{extension}
 * 3. Move file to public/uploads/{entityType}/{uuid}.{extension}
 * 4. Store only the filename in storagePath (NOT full path)
 * 5. Originals served by nginx; resized variants served by LiipImagineBundle
 *
 * Allowed MIME types: image/png, image/jpeg, image/webp.
 * Allowed entity types: items, skills, characters, mobs.
 */
class MediaUploadService
{
    /** Allowed MIME types for image uploads */
    private const ALLOWED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
    ];

    /** Maps MIME types to file extensions */
    private const MIME_TO_EXTENSION = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    /** Allowed entity types that map to upload subdirectories */
    private const ALLOWED_ENTITY_TYPES = [
        'items',
        'skills',
        'characters',
        'mobs',
    ];

    public function __construct(
        private readonly MediaFileRepository $mediaFileRepository,
        private readonly string $uploadDir,
    ) {
    }

    /**
     * Upload an image file, move it to storage, and persist metadata.
     *
     * @param UploadedFile $file       The uploaded file from the request
     * @param string       $entityType Subdirectory/category: items, skills, characters, mobs
     * @param string|null  $entityId   UUID of the related entity (optional)
     *
     * @throws \InvalidArgumentException If MIME type or entity type is not allowed
     */
    public function upload(UploadedFile $file, string $entityType, ?string $entityId = null): MediaFile
    {
        // Validate that the entity type is in the allowed list
        if (!in_array($entityType, self::ALLOWED_ENTITY_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid entity type "%s". Allowed: %s',
                    $entityType,
                    implode(', ', self::ALLOWED_ENTITY_TYPES),
                )
            );
        }

        // Use client-reported MIME type; fall back to guessed MIME if available
        try {
            $mimeType = $file->getMimeType() ?? $file->getClientMimeType();
        } catch (\LogicException) {
            $mimeType = $file->getClientMimeType();
        }

        // Validate that the uploaded file is an allowed image type
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid file type "%s". Allowed: %s', $mimeType, implode(', ', self::ALLOWED_MIME_TYPES))
            );
        }

        $extension = self::MIME_TO_EXTENSION[$mimeType] ?? 'bin';
        $uuid = Uuid::v4()->toRfc4122();
        $filename = sprintf('%s.%s', $uuid, $extension);
        $targetDir = $this->uploadDir . '/' . $entityType;

        // Ensure the target directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Capture file metadata before moving (move destroys the temp file)
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize() ?: 0;

        // Move the uploaded file to its final storage location
        $file->move($targetDir, $filename);

        // Create and persist the MediaFile entity with upload metadata.
        // Only the filename is stored in storagePath; the directory is derived from entityType.
        $mediaFile = new MediaFile();
        $mediaFile->setOriginalFilename($originalName);
        $mediaFile->setStoragePath($filename);
        $mediaFile->setMimeType($mimeType);
        $mediaFile->setFileSize($fileSize);
        $mediaFile->setEntityType($entityType);
        $mediaFile->setEntityId($entityId);

        $this->mediaFileRepository->save($mediaFile);

        return $mediaFile;
    }
}
