<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Health\DTO\HealthDataPointDTO;
use App\Application\Health\DTO\HealthSyncDTO;
use App\Application\Health\Service\HealthSummaryService;
use App\Application\Health\Service\HealthSyncService;
use App\Domain\User\Entity\User;
use App\Infrastructure\Health\Repository\HealthSyncLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API controller for health data synchronization and summaries.
 *
 * Handles health data that originates from Apple HealthKit (iOS) or Google Health Connect (Android).
 * The mobile app reads data from the platform health APIs and sends it here in batches.
 *
 * Provides three endpoints:
 * - POST /api/health/sync      -- receive and store batched health data points
 * - GET  /api/health/summary   -- return aggregated daily health metrics
 * - GET  /api/health/sync-status -- return last sync time per data type
 *
 * All endpoints require JWT authentication.
 *
 * Flow: HealthKit/Health Connect -> Mobile App -> POST /sync -> HealthSyncService -> DB
 *       Mobile App -> GET /summary -> HealthSummaryService -> aggregated JSON response
 */
class HealthController extends AbstractController
{
    public function __construct(
        private readonly HealthSyncService $healthSyncService,
        private readonly HealthSummaryService $healthSummaryService,
        private readonly HealthSyncLogRepository $healthSyncLogRepository,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Receive a batch of health data points from the mobile app.
     *
     * The mobile app collects data from Apple HealthKit or Google Health Connect
     * and sends it as an array of data points with platform identifier.
     * Each point has an externalUuid for deduplication (the ID from the health platform).
     *
     * Returns count of accepted vs. skipped (duplicate) data points.
     */
    #[Route('/api/health/sync', name: 'api_health_sync', methods: ['POST'])]
    public function sync(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(
                ['error' => 'Invalid JSON body.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Map JSON payload to DTO structure: one sync request with multiple data points
        $dto = new HealthSyncDTO();
        $dto->platform = $data['platform'] ?? null;

        $dataPoints = [];
        if (isset($data['dataPoints']) && is_array($data['dataPoints'])) {
            foreach ($data['dataPoints'] as $point) {
                $pointDTO = new HealthDataPointDTO();
                $pointDTO->externalUuid = $point['externalUuid'] ?? null;
                $pointDTO->type = $point['type'] ?? null;
                $pointDTO->value = isset($point['value']) ? (float) $point['value'] : null;
                $pointDTO->unit = $point['unit'] ?? '';
                $pointDTO->dateFrom = $point['dateFrom'] ?? '';
                $pointDTO->dateTo = $point['dateTo'] ?? '';
                $pointDTO->sourceApp = $point['sourceApp'] ?? null;
                $pointDTO->recordingMethod = $point['recordingMethod'] ?? null;
                $dataPoints[] = $pointDTO;
            }
        }
        $dto->dataPoints = $dataPoints;

        // Validate DTO using Symfony Validator (including nested data point DTOs via #[Assert\Valid])
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            return $this->json(
                ['errors' => $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Additional validation: check enum values are valid (beyond basic NotBlank checks)
        $platformValues = array_map(fn($c) => $c->value, \App\Domain\Health\Enum\Platform::cases());
        if (!in_array($dto->platform, $platformValues, true)) {
            return $this->json(
                ['errors' => ['platform' => ['Invalid platform. Must be ios or android.']]],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Validate each data point's type and recording method against known enum values
        foreach ($dto->dataPoints as $i => $pointDTO) {
            $typeValues = array_map(fn($c) => $c->value, \App\Domain\Health\Enum\HealthDataType::cases());
            if (!in_array($pointDTO->type, $typeValues, true)) {
                return $this->json(
                    ['errors' => ["dataPoints[$i].type" => ['Invalid health data type.']]],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $methodValues = array_map(fn($c) => $c->value, \App\Domain\Health\Enum\RecordingMethod::cases());
            if (!in_array($pointDTO->recordingMethod, $methodValues, true)) {
                return $this->json(
                    ['errors' => ["dataPoints[$i].recordingMethod" => ['Invalid recording method.']]],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
        }

        // Delegate to service: deduplicates by externalUuid, persists new points, updates sync log
        $result = $this->healthSyncService->syncHealthData($user, $dto);

        return $this->json($result, Response::HTTP_OK);
    }

    /**
     * Return aggregated daily health summary for the authenticated user.
     *
     * Accepts an optional ?date=YYYY-MM-DD query parameter (defaults to today).
     * Aggregates steps, calories, distance, sleep, heart rate, and workout minutes
     * from stored health data points for that day.
     *
     * Used by the mobile app's daily dashboard screen.
     */
    #[Route('/api/health/summary', name: 'api_health_summary', methods: ['GET'])]
    public function summary(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $dateString = $request->query->get('date', (new \DateTimeImmutable())->format('Y-m-d'));

        try {
            $date = new \DateTimeImmutable($dateString);
        } catch (\Exception) {
            return $this->json(
                ['error' => 'Invalid date format. Use YYYY-MM-DD.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $summary = $this->healthSummaryService->getDailySummary($user, $date);

        return $this->json($summary, Response::HTTP_OK);
    }

    /**
     * Return the sync status for each health data type.
     *
     * Shows when each data type (steps, heart rate, etc.) was last synced and how
     * many points were received. Used by the mobile app to determine which data
     * needs to be re-synced.
     */
    #[Route('/api/health/sync-status', name: 'api_health_sync_status', methods: ['GET'])]
    public function syncStatus(#[CurrentUser] User $user): JsonResponse
    {
        $logs = $this->healthSyncLogRepository->getSyncStatus($user);

        $result = [];
        foreach ($logs as $log) {
            $result[$log->getDataType()->value] = [
                'last_synced_at' => $log->getLastSyncedAt()->format(\DateTimeInterface::ATOM),
                'points_count' => $log->getPointsCount(),
            ];
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
