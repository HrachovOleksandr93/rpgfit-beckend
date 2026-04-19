<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\TargetUserResolver;
use App\Application\Test\Service\TestHarnessGate;
use App\Application\Test\Service\TestHarnessRateLimiter;
use App\Controller\PsychProfileController;
use App\Domain\PsychProfile\Entity\PsychCheckIn;
use App\Domain\PsychProfile\Entity\PsychUserProfile;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Test-harness helper for the Psych Profiler (spec 2026-04-18 §5.8).
 *
 * Seeds N back-dated check-ins with a given status so Playwright /
 * debug-drawer flows can trigger the crisis-detection log without waiting
 * real days. Respects BOTH the Phase-6 kill-switch (`assertEnabled()`
 * inherited from `AbstractTestController`) AND the feature flag for the
 * Psych Profiler itself — a 404 is returned unless both are truthy.
 */
#[Route('/api/test')]
#[IsGranted('ROLE_TESTER')]
final class PsychTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly PsychCheckInRepository $checkInRepository,
        private readonly PsychUserProfileRepository $profileRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/psych/seed', name: 'api_test_psych_seed', methods: ['POST'])]
    public function seed(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->assertPsychFeatureEnabled();
        $this->enforceRateLimit($currentUser, 'psych.seed');

        $target = $this->resolveTarget($request, $currentUser);
        $body = $this->decodeBody($request);

        $days = (int) ($body['days'] ?? 0);
        if ($days < 1 || $days > 90) {
            throw new BadRequestHttpException('days must be between 1 and 90.');
        }

        $statusRaw = (string) ($body['status'] ?? '');
        $status = PsychStatus::tryFrom($statusRaw);
        if ($status === null) {
            throw new BadRequestHttpException(sprintf(
                'status must be one of %s.',
                implode(', ', array_map(static fn (PsychStatus $c) => $c->value, PsychStatus::cases())),
            ));
        }

        // Wipe any existing rows that overlap the seed window so the test
        // scenario starts from a deterministic slate.
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $this->wipeWindow($target, $today, $days);

        $profile = $this->profileRepository->findOrCreateForUser($target);
        $created = 0;
        $lastRow = null;
        for ($offset = $days - 1; $offset >= 0; --$offset) {
            $day = $today->modify(sprintf('-%d day', $offset));

            $row = new PsychCheckIn();
            $row->setUser($target)
                ->setAssignedStatus($status)
                ->setSkipped(false)
                ->setCheckedInOn($day)
                ->setCreatedAt($day);
            $this->entityManager->persist($row);

            $lastRow = $row;
            ++$created;
        }

        // Align the profile with the most recent seeded row so downstream
        // queries (e.g. TodayService, PsychStatusModifierService) observe
        // the synthetic state.
        if ($lastRow !== null) {
            $profile->setCurrentStatus($status)
                ->setStatusValidUntil($today->modify('+1 day'))
                ->setLastCheckInAt($lastRow->getCreatedAt());
        }

        $this->entityManager->flush();

        $payload = [
            'created' => $created,
            'status' => $status->value,
            'windowDays' => $days,
        ];
        $auditId = $this->audit($currentUser, $target, 'psych.seed', $payload);

        return $this->json(
            $this->envelope($currentUser, $target, $auditId, $payload),
            Response::HTTP_CREATED,
        );
    }

    /**
     * Defence-in-depth: the feature flag for the Psych Profiler must be on
     * before we allow seed mutations that assume the schema and services
     * are wired up in this environment.
     */
    private function assertPsychFeatureEnabled(): void
    {
        $raw = (string) ($_SERVER[PsychProfileController::ENV_FLAG]
            ?? $_ENV[PsychProfileController::ENV_FLAG]
            ?? '');

        if (!in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true)) {
            throw new NotFoundHttpException('Not found.');
        }
    }

    /** Remove rows in `[today - days + 1 .. today]` so the seed starts clean. */
    private function wipeWindow(User $user, \DateTimeImmutable $today, int $days): void
    {
        $start = $today->modify(sprintf('-%d day', max(0, $days - 1)));
        $existing = $this->checkInRepository->findInRange($user, $start, $today);
        foreach ($existing as $row) {
            $this->entityManager->remove($row);
        }
        if ($existing !== []) {
            $this->entityManager->flush();
        }
    }
}
