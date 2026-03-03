<?php

namespace App\Domain\Communication\Services;

use App\Domain\Communication\DTOs\IdempotencyStartResult;
use App\Models\IdempotencyKey;
use App\Models\Message;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class IdempotencyService
{
    private const STALE_PROCESSING_MINUTES = 5;

    public function start(int $userId, string $key, string $endpoint, array $payload): IdempotencyStartResult
    {
        $hash = $this->payloadHash($payload);

        return DB::transaction(function () use ($userId, $key, $endpoint, $hash) {
            try {
                $row = IdempotencyKey::create([
                    'user_id' => $userId,
                    'idempotency_key' => $key,
                    'endpoint' => $endpoint,
                    'request_hash' => $hash,
                    'status' => 'processing',
                ]);

                return new IdempotencyStartResult(IdempotencyStartResult::MODE_NEW, $row, null, null);
            } catch (QueryException $e) {
                if ($this->isDuplicateKeyException($e)) {
                    // Row exists; select with lock and resolve replay/conflict
                    $existing = IdempotencyKey::where('user_id', $userId)
                        ->where('idempotency_key', $key)
                        ->where('endpoint', $endpoint)
                        ->lockForUpdate()
                        ->first();

                    return $this->resolveExistingRow($existing, $hash);
                }
                throw $e;
            }
        });
    }

    private function resolveExistingRow(?IdempotencyKey $existing, string $hash): IdempotencyStartResult
    {
        if ($existing === null) {
            return new IdempotencyStartResult(
                IdempotencyStartResult::MODE_CONFLICT,
                null,
                null,
                IdempotencyStartResult::REASON_HASH_MISMATCH
            );
        }

        if ($existing->request_hash !== $hash) {
            return new IdempotencyStartResult(
                IdempotencyStartResult::MODE_CONFLICT,
                $existing,
                null,
                IdempotencyStartResult::REASON_HASH_MISMATCH
            );
        }

            if ($existing->status === 'completed') {
                if ($existing->message_id !== null) {
                    $message = Message::find($existing->message_id);
                    if ($message === null) {
                        return new IdempotencyStartResult(
                            IdempotencyStartResult::MODE_CONFLICT,
                            $existing,
                            null,
                            IdempotencyStartResult::REASON_REPLAY_TARGET_MISSING
                        );
                    }

                    return new IdempotencyStartResult(
                        IdempotencyStartResult::MODE_REPLAY,
                        $existing,
                        $message,
                        null
                    );
                }

                $payload = is_array($existing->response_payload) ? $existing->response_payload : null;
                if ($payload === null) {
                    return new IdempotencyStartResult(
                        IdempotencyStartResult::MODE_CONFLICT,
                        $existing,
                        null,
                        IdempotencyStartResult::REASON_REPLAY_TARGET_MISSING
                    );
                }

                return new IdempotencyStartResult(
                    IdempotencyStartResult::MODE_REPLAY,
                    $existing,
                    null,
                    null,
                    $payload
                );
            }

            if ($existing->status === 'failed') {
                return new IdempotencyStartResult(
                    IdempotencyStartResult::MODE_CONFLICT,
                    $existing,
                    null,
                    IdempotencyStartResult::REASON_PREVIOUS_FAILED_USE_NEW_KEY
                );
            }

            if ($existing->status === 'processing') {
                $staleThreshold = now()->subMinutes(self::STALE_PROCESSING_MINUTES);
                if ($existing->updated_at <= $staleThreshold) {
                    $existing->update([
                        'status' => 'failed',
                        'error_message' => 'stale_processing_timeout',
                        'processed_at' => now(),
                    ]);

                    return new IdempotencyStartResult(
                        IdempotencyStartResult::MODE_CONFLICT,
                        $existing,
                        null,
                        IdempotencyStartResult::REASON_PREVIOUS_FAILED_USE_NEW_KEY
                    );
                }

                return new IdempotencyStartResult(
                    IdempotencyStartResult::MODE_CONFLICT,
                    $existing,
                    null,
                    IdempotencyStartResult::REASON_IN_PROGRESS
                );
            }

            return new IdempotencyStartResult(
                IdempotencyStartResult::MODE_CONFLICT,
                $existing,
                null,
                IdempotencyStartResult::REASON_PREVIOUS_FAILED_USE_NEW_KEY
            );
    }

    private function isDuplicateKeyException(QueryException $e): bool
    {
        $code = $e->getCode();
        // MySQL ER_DUP_ENTRY = 1062, SQLSTATE 23000
        return $code === 1062 || $code === '23000' || (string) $code === '23000';
    }

    public function complete(IdempotencyKey $row, int $messageId): void
    {
        $update = [
            'status' => 'completed',
            'message_id' => $messageId,
            'processed_at' => now(),
        ];

        if ($this->hasReplayColumns()) {
            $update['reference_type'] = null;
            $update['reference_id'] = null;
            $update['response_payload'] = null;
        }

        $row->update($update);
    }

    public function completeWithPayload(
        IdempotencyKey $row,
        string $referenceType,
        string $referenceId,
        array $responsePayload
    ): void {
        $update = [
            'status' => 'completed',
            'message_id' => null,
            'processed_at' => now(),
        ];

        if ($this->hasReplayColumns()) {
            $update['reference_type'] = $referenceType;
            $update['reference_id'] = $referenceId;
            $update['response_payload'] = $responsePayload;
        }

        $row->update($update);
    }

    public function fail(IdempotencyKey $row, string $errorMessage): void
    {
        $row->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);

        Log::warning('communication.idempotency.failed', [
            'user_id' => $row->user_id,
            'endpoint' => $row->endpoint,
            'key' => $row->idempotency_key,
            'reason' => $errorMessage,
        ]);
    }

    public function payloadHash(array $payload): string
    {
        $payload = $this->sortRecursive($payload);

        return hash('sha256', json_encode($payload));
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private function sortRecursive(array $value): array
    {
        if (array_is_list($value)) {
            $mapped = array_map(function ($item) {
                return is_array($item) ? $this->sortRecursive($item) : $item;
            }, $value);
            usort($mapped, static function ($a, $b): int {
                return strcmp(json_encode($a), json_encode($b));
            });
            return $mapped;
        }

        ksort($value);
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        return $value;
    }

    private function hasReplayColumns(): bool
    {
        static $hasColumns = null;
        if ($hasColumns !== null) {
            return $hasColumns;
        }

        $hasColumns = Schema::hasColumn('idempotency_keys', 'reference_type')
            && Schema::hasColumn('idempotency_keys', 'reference_id')
            && Schema::hasColumn('idempotency_keys', 'response_payload');

        return $hasColumns;
    }
}
