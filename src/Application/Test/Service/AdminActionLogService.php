<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Domain\Test\Entity\AdminActionLog;
use App\Domain\Test\Enum\ReasonEnum;
use App\Domain\User\Entity\User;
use App\Infrastructure\Test\Repository\AdminActionLogRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Application-layer orchestrator that records a single admin / tester
 * action to both the database and the `audit` Monolog channel.
 *
 * Not marked `final` — tests mock this class wherever `TargetUserResolver`
 * collaborates with it, and mocking a final class requires ProphecyBridge
 * or @final-annotated hacks.
 */
class AdminActionLogService
{
    private readonly LoggerInterface $auditLogger;

    /**
     * @param LoggerInterface|null $auditLogger Tagged `audit` channel logger.
     *                                          Nullable so the service remains
     *                                          wirable before monolog-bundle is
     *                                          installed; a NullLogger is used
     *                                          as the fallback sink.
     */
    public function __construct(
        private readonly AdminActionLogRepository $repository,
        ?LoggerInterface $auditLogger = null,
    ) {
        $this->auditLogger = $auditLogger ?? new NullLogger();
    }

    /**
     * Persist an audit record and emit a structured log entry.
     *
     * @param array<string, mixed> $payload Extra structured context.
     */
    public function record(
        User $actor,
        ?User $target,
        string $action,
        ?ReasonEnum $reason,
        array $payload = [],
    ): AdminActionLog {
        $log = new AdminActionLog();
        $log->setActor($actor)
            ->setTarget($target)
            ->setAction($action)
            ->setReason($reason)
            ->setPayload($payload);

        $this->repository->save($log);

        $this->auditLogger->info('admin_action', [
            'log_id' => $log->getId()->toRfc4122(),
            'actor_id' => $actor->getId()->toRfc4122(),
            'actor_login' => $actor->getLogin(),
            'target_id' => $target?->getId()->toRfc4122(),
            'target_login' => $target?->getLogin(),
            'action' => $action,
            'reason' => $reason?->value,
            'payload' => $payload,
            'created_at' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);

        return $log;
    }
}
