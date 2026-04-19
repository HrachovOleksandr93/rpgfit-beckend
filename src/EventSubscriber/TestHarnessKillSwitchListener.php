<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Application\Test\Service\TestHarnessGate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Short-circuit `/api/test/*` requests to 404 when the harness is disabled.
 *
 * Matches the spec §6.3: callers should not be able to tell whether the
 * endpoint exists and is disabled or doesn't exist at all. We deliberately
 * throw `NotFoundHttpException` rather than `AccessDeniedHttpException`.
 *
 * Runs very early in the kernel.request lifecycle (priority 300) so the
 * guard fires before routing/security pick up the request.
 */
final class TestHarnessKillSwitchListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly TestHarnessGate $gate,
    ) {
    }

    /** @return array<string, array<int, array<int, int|string>>> */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 300]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (!str_starts_with($path, '/api/test')) {
            return;
        }

        // Meta endpoints must stay reachable so a superadmin can toggle the
        // flag on/off from the app when the env var is `false`. We still
        // enforce role-based access inside the controller via #[IsGranted].
        if (
            str_starts_with($path, '/api/test/meta/enable')
            || str_starts_with($path, '/api/test/meta/disable')
            || str_starts_with($path, '/api/test/meta/status')
        ) {
            return;
        }

        if ($this->gate->isEnabled()) {
            return;
        }

        throw new NotFoundHttpException('Not found.');
    }
}
