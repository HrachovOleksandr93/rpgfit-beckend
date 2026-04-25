<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\PsychProfile\DTO\CheckInRequest;
use App\Application\PsychProfile\DTO\CheckInResponse;
use App\Application\PsychProfile\Service\CheckInService;
use App\Application\PsychProfile\Service\CrisisDetectionService;
use App\Application\PsychProfile\Service\PhysicalStateService;
use App\Application\PsychProfile\Service\ProfileTrendService;
use App\Application\PsychProfile\Service\PsychStatusModifierService;
use App\Application\PsychProfile\Service\TodayService;
use App\Domain\PsychProfile\Enum\MoodQuadrant;
use App\Domain\PsychProfile\Enum\UserIntent;
use App\Domain\User\Entity\User;
use App\Infrastructure\Battle\Repository\WorkoutSessionRepository;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\PsychProfile\Repository\PhysicalStateAnswerRepository;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * HTTP controller for the Psych Profiler feature (spec 2026-04-18 §5.4 + v2 2026-04-19 §2.4).
 *
 * Presentation layer. Every action is:
 *  - auth-gated via `#[IsGranted('ROLE_USER')]`
 *  - feature-flag-gated via `assertEnabled()` (404 when the env var is off)
 *
 * V2 additions (2026-04-19):
 *  - POST /api/psych/physical-state    (Q4 / session-RPE submit)
 *  - GET  /api/psych/deload-suggestion (day-5+ deload card / day-7+ plan)
 *  - POST /api/psych/check-in          accepts optional `rpeScore`
 *
 * V2 endpoints respect an additional `feature.psych_profiler.v2.enabled`
 * flag (env OR game_setting). When OFF, they return 404 while v1 paths
 * continue to work.
 *
 * Business logic lives in `App\Application\PsychProfile\Service\*` — this
 * controller is a thin orchestrator that decodes the request, forwards to
 * the relevant service and serialises the response.
 */
#[Route('/api/psych')]
#[IsGranted('ROLE_USER')]
final class PsychProfileController extends AbstractController
{
    /** Env var that globally toggles the feature (spec §5.4). */
    public const ENV_FLAG = 'PSYCH_PROFILER_ENABLED';

    /** Env var / game_setting that toggles v2 (spec 2026-04-19). */
    public const ENV_FLAG_V2 = 'PSYCH_PROFILER_V2_ENABLED';
    public const SETTING_FLAG_V2 = 'feature.psych_profiler.v2.enabled';

    public function __construct(
        private readonly TodayService $todayService,
        private readonly CheckInService $checkInService,
        private readonly ProfileTrendService $profileTrendService,
        private readonly PsychStatusModifierService $modifierService,
        private readonly PsychCheckInRepository $checkInRepository,
        private readonly PsychUserProfileRepository $profileRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly ?PhysicalStateService $physicalStateService = null,
        private readonly ?PhysicalStateAnswerRepository $physicalStateRepository = null,
        private readonly ?CrisisDetectionService $crisisDetectionService = null,
        private readonly ?WorkoutSessionRepository $workoutSessionRepository = null,
        private readonly ?GameSettingRepository $gameSettingRepository = null,
    ) {
    }

    #[Route('/today', name: 'api_psych_today', methods: ['GET'])]
    public function today(#[CurrentUser] User $user): JsonResponse
    {
        $this->assertEnabled();

        return $this->json($this->todayService->getToday($user)->toArray());
    }

    #[Route('/check-in', name: 'api_psych_check_in', methods: ['POST'])]
    public function checkIn(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->assertEnabled();

        $dto = CheckInRequest::fromArray($this->decodeBody($request));
        $this->assertValid($dto);

        // Q4 only accepted when v2 is enabled; silently drop otherwise.
        $rpeScore = $this->isV2Enabled() ? $dto->rpeScore : null;

        $checkIn = $this->checkInService->checkIn(
            user: $user,
            mood: $dto->mood !== null ? MoodQuadrant::from($dto->mood) : null,
            energy: $dto->energy,
            intent: $dto->intent !== null ? UserIntent::from($dto->intent) : null,
            skipped: $dto->skipped,
            rpeScore: $rpeScore,
        );

        $profile = $this->profileRepository->findOrCreateForUser($user);
        $this->entityManager->flush();

        $xpHint = $this->modifierService->getXpMultiplier(
            $user,
            PsychStatusModifierService::CONTEXT_BATTLE,
        );

        $response = CheckInResponse::fromCheckIn(
            checkIn: $checkIn,
            statusValidUntil: $profile->getStatusValidUntil(),
            xpMultiplierHint: $xpHint,
        );

        return $this->json($response->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/physical-state', name: 'api_psych_physical_state', methods: ['POST'])]
    public function physicalState(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->assertEnabled();
        $this->assertV2Enabled();
        if ($this->physicalStateService === null) {
            throw new NotFoundHttpException('Not found.');
        }

        $body = $this->decodeBody($request);

        $rpeScore = isset($body['rpeScore']) ? (int) $body['rpeScore'] : 0;
        if ($rpeScore < 1 || $rpeScore > 5) {
            throw new BadRequestHttpException('rpeScore must be between 1 and 5.');
        }

        $session = null;
        if (isset($body['workoutSessionId']) && is_string($body['workoutSessionId'])
            && $body['workoutSessionId'] !== '' && $this->workoutSessionRepository !== null) {
            if (!Uuid::isValid($body['workoutSessionId'])) {
                throw new BadRequestHttpException('workoutSessionId must be a valid UUID.');
            }
            $session = $this->workoutSessionRepository->find($body['workoutSessionId']);
            if ($session !== null
                && $session->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
                throw new BadRequestHttpException('workoutSessionId does not belong to the current user.');
            }
        }

        $answer = $this->physicalStateService->record($user, $session, $rpeScore);

        return $this->json([
            'id' => $answer->getId()->toRfc4122(),
            'rpeScore' => $answer->getRpeScore(),
            'workoutSessionId' => $answer->getWorkoutSession()?->getId()->toRfc4122(),
            'createdAt' => $answer->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_CREATED);
    }

    #[Route('/deload-suggestion', name: 'api_psych_deload_suggestion', methods: ['GET'])]
    public function deloadSuggestion(#[CurrentUser] User $user): JsonResponse
    {
        $this->assertEnabled();
        $this->assertV2Enabled();
        if ($this->crisisDetectionService === null) {
            throw new NotFoundHttpException('Not found.');
        }

        $streak = $this->crisisDetectionService->getWearyStreakDays($user);
        $cardStart = $this->loadIntSetting('psych.deload_card_start_day', 5);
        $planStart = $this->loadIntSetting('psych.deload_plan_start_day', 7);

        $showCard = $streak >= $cardStart;
        $showPlan = $streak >= $planStart;

        return $this->json([
            'showCard' => $showCard,
            'streakDays' => $streak,
            'recommendedPlan' => $showPlan ? $this->buildDeloadPlan() : null,
        ]);
    }

    #[Route('/trend', name: 'api_psych_trend', methods: ['GET'])]
    public function trend(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->assertEnabled();

        $window = (int) $request->query->get('window', '7');
        if (!in_array($window, ProfileTrendService::ALLOWED_WINDOWS, true)) {
            throw new BadRequestHttpException('window must be one of 7, 30 or 90.');
        }

        return $this->json($this->profileTrendService->getTrend($user, $window)->toArray());
    }

    #[Route('/opt-in', name: 'api_psych_opt_in', methods: ['POST'])]
    public function optIn(#[CurrentUser] User $user): JsonResponse
    {
        $this->assertEnabled();

        $profile = $this->profileRepository->findOrCreateForUser($user);
        $profile->setFeatureOptedIn(true);
        $this->entityManager->flush();

        return $this->json([
            'featureOptedIn' => $profile->isFeatureOptedIn(),
        ]);
    }

    #[Route('/opt-out', name: 'api_psych_opt_out', methods: ['POST'])]
    public function optOut(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->assertEnabled();

        $profile = $this->profileRepository->findOrCreateForUser($user);
        $profile->setFeatureOptedIn(false);

        $erase = $this->flagTrue((string) $request->query->get('erase', '0'));
        $erasedCount = 0;
        if ($erase) {
            $erasedCount = $this->checkInRepository->deleteAllForUser($user);
            // v2 — also purge Q4 answers when the user opts for full erasure.
            if ($this->physicalStateRepository !== null) {
                $this->physicalStateRepository->deleteAllForUser($user);
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'featureOptedIn' => $profile->isFeatureOptedIn(),
            'erased' => $erase,
            'erasedCount' => $erasedCount,
        ]);
    }

    #[Route('/export', name: 'api_psych_export', methods: ['GET'])]
    public function export(#[CurrentUser] User $user): JsonResponse
    {
        $this->assertEnabled();

        $profile = $this->profileRepository->findByUser($user);
        $rows = $this->checkInRepository->findLatestForUser($user, 10_000);

        $checkIns = [];
        foreach ($rows as $row) {
            $checkIns[] = [
                'id' => $row->getId()->toRfc4122(),
                'checkedInOn' => $row->getCheckedInOn()->format('Y-m-d'),
                'mood' => $row->getMoodQuadrant()?->value,
                'energy' => $row->getEnergyLevel(),
                'intent' => $row->getIntent()?->value,
                'assignedStatus' => $row->getAssignedStatus()->value,
                'skipped' => $row->isSkipped(),
                'rpeScore' => $row->getPhysicalStateAnswer()?->getRpeScore(),
                'createdAt' => $row->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $this->json([
            'profile' => $profile === null ? null : [
                'currentStatus' => $profile->getCurrentStatus()->value,
                'statusValidUntil' => $profile->getStatusValidUntil()->format(\DateTimeInterface::ATOM),
                'consecutiveSkips' => $profile->getConsecutiveSkips(),
                'featureOptedIn' => $profile->isFeatureOptedIn(),
                'lastCheckInAt' => $profile->getLastCheckInAt()?->format(\DateTimeInterface::ATOM),
                'trends' => $profile->getTrends(),
            ],
            'checkIns' => $checkIns,
        ]);
    }

    #[Route('/history', name: 'api_psych_history_delete', methods: ['DELETE'])]
    public function deleteHistory(#[CurrentUser] User $user): Response
    {
        $this->assertEnabled();

        $this->checkInRepository->deleteAllForUser($user);
        if ($this->physicalStateRepository !== null) {
            $this->physicalStateRepository->deleteAllForUser($user);
        }

        $profile = $this->profileRepository->findByUser($user);
        if ($profile !== null) {
            $profile->setConsecutiveSkips(0)
                ->setLastCheckInAt(null)
                ->setTrends([]);
        }
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Run symfony/validator on the payload and fail the request on any
     * violation. Also enforces the "all three answers required when not
     * skipped" rule here so CheckInService stays input-agnostic.
     */
    private function assertValid(CheckInRequest $dto): void
    {
        $violations = $this->validator->validate($dto);
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
                ];
            }
            throw new BadRequestHttpException(
                json_encode(['errors' => $errors], JSON_UNESCAPED_UNICODE) ?: 'Invalid payload.',
            );
        }

        if (!$dto->skipped && ($dto->mood === null || $dto->energy === null || $dto->intent === null)) {
            throw new BadRequestHttpException('mood, energy and intent are required when skipped=false.');
        }
    }

    /**
     * Feature-flag guard — env var `PSYCH_PROFILER_ENABLED` must be truthy.
     * When off, the API silently returns 404 so unauthorised probing cannot
     * distinguish "feature missing" from "feature disabled".
     */
    private function assertEnabled(): void
    {
        $raw = (string) ($_SERVER[self::ENV_FLAG]
            ?? $_ENV[self::ENV_FLAG]
            ?? '');

        if (!in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true)) {
            throw new NotFoundHttpException('Not found.');
        }
    }

    /**
     * V2 feature flag — truthy env var OR truthy game_setting row. When
     * OFF, the v2-only endpoints return 404 and the `rpeScore` field on
     * /check-in is silently ignored.
     */
    public function isV2Enabled(): bool
    {
        $env = (string) ($_SERVER[self::ENV_FLAG_V2] ?? $_ENV[self::ENV_FLAG_V2] ?? '');
        if (in_array(strtolower($env), ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if ($this->gameSettingRepository === null) {
            return false;
        }

        $setting = $this->gameSettingRepository->findByKey(self::SETTING_FLAG_V2);
        if ($setting === null) {
            return false;
        }

        return in_array(strtolower(trim($setting->getValue())), ['1', 'true', 'yes', 'on'], true);
    }

    private function assertV2Enabled(): void
    {
        if (!$this->isV2Enabled()) {
            throw new NotFoundHttpException('Not found.');
        }
    }

    private function loadIntSetting(string $key, int $default): int
    {
        if ($this->gameSettingRepository === null) {
            return $default;
        }
        $setting = $this->gameSettingRepository->findByKey($key);
        if ($setting === null) {
            return $default;
        }
        $value = (int) $setting->getValue();

        return $value > 0 ? $value : $default;
    }

    /**
     * Build the recommended deload-plan shell (spec §1.5). We stop short
     * of generating a full WorkoutPlan here because the founder wants the
     * suggestion to appear before the user commits — the client asks
     * `/api/workout/generate` with `?deload=1` (future hook) to actually
     * persist a plan. For now we return the intent so the UI can render
     * a pre-commitment modal.
     *
     * @return array<string, mixed>
     */
    private function buildDeloadPlan(): array
    {
        $volumeReductionPct = $this->loadIntSetting('psych.deload_volume_reduction_pct', 40);
        $intensityReductionPct = $this->loadIntSetting('psych.deload_intensity_reduction_pct', 30);

        return [
            'type' => 'deload_week',
            'durationDays' => 7,
            'volumeReductionPct' => $volumeReductionPct,
            'intensityReductionPct' => $intensityReductionPct,
            'focus' => 'recovery',
            'copyUa' => 'Тиждень зцілення. Зменшуємо навантаження.',
            'copyEn' => 'Recovery week. Load dialed back.',
        ];
    }

    /** @return array<string, mixed> */
    private function decodeBody(Request $request): array
    {
        $raw = $request->getContent();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function flagTrue(string $raw): bool
    {
        return in_array(strtolower(trim($raw)), ['1', 'true', 'yes', 'on'], true);
    }
}
