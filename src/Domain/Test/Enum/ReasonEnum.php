<?php

declare(strict_types=1);

namespace App\Domain\Test\Enum;

/**
 * Canonical list of reasons an admin can cite when mutating another user's
 * test-harness state. Recording a structured reason (rather than a free-text
 * blob) keeps audit logs queryable and discourages casual impersonation.
 *
 * Used by `TargetUserResolver` whenever an `asUserId` override is present.
 */
enum ReasonEnum: string
{
    case PLAYTEST = 'playtest';
    case BUG_REPRO = 'bug_repro';
    case DATA_FIX = 'data_fix';
    case CONTENT_QA = 'content_qa';
    case LIVE_DEBUG = 'live_debug';
    case SUPPORT_TICKET = 'support_ticket';

    /** Human-readable label for UI / CLI output. */
    public function label(): string
    {
        return match ($this) {
            self::PLAYTEST => 'Playtest',
            self::BUG_REPRO => 'Bug reproduction',
            self::DATA_FIX => 'Data fix',
            self::CONTENT_QA => 'Content QA',
            self::LIVE_DEBUG => 'Live debug',
            self::SUPPORT_TICKET => 'Support ticket',
        };
    }
}
