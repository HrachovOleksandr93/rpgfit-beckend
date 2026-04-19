<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Domain\Test\Entity\AdminActionLog;
use App\Infrastructure\Test\Repository\AdminActionLogRepository;

/**
 * Read-side helper: pull the last N audit rows for the admin dashboard /
 * test-harness debug drawer.
 */
final class AuditQueryService
{
    public function __construct(
        private readonly AdminActionLogRepository $repository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit): array
    {
        $limit = max(1, min(200, $limit));
        $rows = $this->repository->findRecent($limit);

        return array_map(static fn (AdminActionLog $log): array => [
            'id' => $log->getId()->toRfc4122(),
            'action' => $log->getAction(),
            'reason' => $log->getReason()?->value,
            'payload' => $log->getPayload(),
            'createdAt' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'actor' => [
                'id' => $log->getActor()->getId()->toRfc4122(),
                'login' => $log->getActor()->getLogin(),
            ],
            'target' => $log->getTarget() !== null ? [
                'id' => $log->getTarget()->getId()->toRfc4122(),
                'login' => $log->getTarget()->getLogin(),
            ] : null,
        ], $rows);
    }
}
