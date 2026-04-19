<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\DTO\TriggerEventRequest;
use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\EventTestService;
use App\Application\Test\Service\TargetUserResolver;
use App\Application\Test\Service\TestHarnessGate;
use App\Application\Test\Service\TestHarnessRateLimiter;
use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Event trigger endpoint (spec §11 Q6). Admin-only.
 *
 * The underlying D5 social-event service is not yet implemented — the
 * stub in `EventTestService` just records the intent; the controller
 * enforces authorization so Playwright already exercises the full path.
 */
#[Route('/api/test')]
#[IsGranted('ROLE_ADMIN')]
final class EventTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly EventTestService $eventTestService,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/event/trigger', name: 'api_test_event_trigger', methods: ['POST'])]
    public function trigger(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'event.trigger');

        $dto = TriggerEventRequest::fromArray($this->decodeBody($request));
        if ($dto->eventSlug === '') {
            return $this->json(['error' => 'eventSlug is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->eventTestService->trigger($currentUser, $dto->eventSlug, $dto->durationMin);
        $auditId = $this->audit($currentUser, null, 'event.trigger', $result);

        return $this->json($this->envelope($currentUser, null, $auditId, $result));
    }
}
