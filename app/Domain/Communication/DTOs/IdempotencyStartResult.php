<?php

namespace App\Domain\Communication\DTOs;

use App\Models\IdempotencyKey;
use App\Models\Message;

final class IdempotencyStartResult
{
    public const MODE_NEW = 'new';
    public const MODE_REPLAY = 'replay';
    public const MODE_CONFLICT = 'conflict';

    public const REASON_HASH_MISMATCH = 'hash_mismatch';
    public const REASON_IN_PROGRESS = 'in_progress';
    public const REASON_PREVIOUS_FAILED_USE_NEW_KEY = 'previous_failed_use_new_key';
    public const REASON_REPLAY_TARGET_MISSING = 'replay_target_missing';

    public function __construct(
        public readonly string $mode,
        public readonly ?IdempotencyKey $row,
        public readonly ?Message $message = null,
        public readonly ?string $reason = null,
        public readonly ?array $responsePayload = null,
    ) {}
}
