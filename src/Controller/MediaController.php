<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Media\Service\MediaUploadService;
use App\Domain\User\Entity\User;
use App\Infrastructure\Media\Repository\MediaFileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * API controller for media file uploads and metadata retrieval.
 *
 * Handles image uploads for game entities (items, skills, characters, mobs).
 * Files are stored on disk and served directly by nginx. Resized variants
 * (thumbnails, icons) are generated on-the-fly by LiipImagineBundle.
 *
 * Media pipeline:
 * - Upload: POST /api/media/upload -> store in public/uploads/{entityType}/
 * - Original: served by nginx from /uploads/{entityType}/{filename}
 * - Resized:  served by LiipImagine from /media/cache/resolve/{filter}/{entityType}/{filename}
 *
 * Endpoints:
 * - POST /api/media/upload -- upload an image (multipart form)
 * - GET  /api/media/{id}   -- get file metadata with thumbnail URLs
 *
 * All endpoints require JWT authentication.
 */
class MediaController extends AbstractController
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
        private readonly MediaFileRepository $mediaFileRepository,
    ) {
    }

    /**
     * Upload a media file (image).
     *
     * Accepts multipart form data with:
     * - file: the image file (required)
     * - entityType: category/subdirectory - items, skills, characters, mobs (required)
     * - entityId: UUID of the related entity (optional)
     *
     * Returns 201 with file metadata including LiipImagine thumbnail URLs.
     */
    #[Route('/api/media/upload', name: 'api_media_upload', methods: ['POST'])]
    public function upload(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $file = $request->files->get('file');

        if ($file === null) {
            return $this->json(
                ['error' => 'No file provided. Use "file" form field.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $entityType = $request->request->get('entityType', '');
        $entityId = $request->request->get('entityId');

        if (empty($entityType)) {
            return $this->json(
                ['error' => 'entityType is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $mediaFile = $this->mediaUploadService->upload($file, $entityType, $entityId);
        } catch (\InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->json($this->buildMediaResponse($mediaFile), Response::HTTP_CREATED);
    }

    /**
     * Get metadata for an uploaded media file.
     *
     * The actual file is served by nginx from /uploads/. This endpoint provides
     * metadata such as original filename, mime type, file size, and LiipImagine
     * thumbnail URLs for different sizes.
     */
    #[Route('/api/media/{id}', name: 'api_media_show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $mediaFile = $this->mediaFileRepository->find($id);

        if ($mediaFile === null) {
            return $this->json(
                ['error' => 'Media file not found.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $response = $this->buildMediaResponse($mediaFile);
        $response['entityId'] = $mediaFile->getEntityId();
        $response['uploadedAt'] = $mediaFile->getUploadedAt()->format(\DateTimeInterface::ATOM);

        return $this->json($response);
    }

    /**
     * Build a standard JSON response payload for a MediaFile.
     *
     * Includes the original URL and LiipImagine thumbnail URLs for icon,
     * thumbnail, and medium filter sets.
     *
     * @return array<string, mixed>
     */
    private function buildMediaResponse(\App\Domain\Media\Entity\MediaFile $mediaFile): array
    {
        $relativePath = $mediaFile->getRelativePath();

        return [
            'id' => $mediaFile->getId()->toRfc4122(),
            'filename' => $mediaFile->getStoragePath(),
            'originalFilename' => $mediaFile->getOriginalFilename(),
            'url' => $mediaFile->getPublicUrl(),
            'thumbnails' => [
                'icon' => '/media/cache/resolve/icon/' . $relativePath,
                'thumbnail' => '/media/cache/resolve/thumbnail/' . $relativePath,
                'medium' => '/media/cache/resolve/medium/' . $relativePath,
            ],
            'entityType' => $mediaFile->getEntityType(),
            'mimeType' => $mediaFile->getMimeType(),
            'fileSize' => $mediaFile->getFileSize(),
        ];
    }
}
