<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillWaConversationWaNumber extends Command
{
    protected $signature = 'communication:backfill-wa-conversation-wa-number
                            {--dry-run : Preview updates without writing to DB}
                            {--user-id= : Process only a single tenant (user_id)}
                            {--conversation-id= : Process only a single conversation_id}
                            {--chunk=500 : Chunk size for state processing}';

    protected $description = 'One-time strict backfill for wa_conversation_states.wa_number_id from historical message metadata';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user-id');
        $conversationId = $this->option('conversation-id');
        $chunk = max(1, (int) ($this->option('chunk') ?: 500));

        $query = WaConversationState::query()
            ->whereNull('wa_number_id');

        if ($userId !== null && $userId !== '') {
            $query->where('user_id', (int) $userId);
        }
        if ($conversationId !== null && $conversationId !== '') {
            $query->where('conversation_id', (int) $conversationId);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No wa_conversation_states rows with null wa_number_id matched filters.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN mode enabled. No database updates will be performed.');
        }

        $stats = [
            'scanned' => 0,
            'updated' => 0,
            'unresolved' => 0,
            'ambiguous' => 0,
        ];
        $unresolvedSample = [];
        $ambiguousSample = [];

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->orderBy('id')
            ->chunkById($chunk, function ($states) use ($dryRun, &$stats, &$unresolvedSample, &$ambiguousSample, $bar) {
                foreach ($states as $state) {
                    $stats['scanned']++;

                    $resolution = $this->resolveWaNumberId($state);
                    $outcome = $resolution['outcome'];

                    if ($outcome === 'resolved') {
                        $stats['updated']++;
                        if (! $dryRun) {
                            $state->update(['wa_number_id' => (int) $resolution['wa_number_id']]);
                        }
                        Log::info('communication.whatsapp.wa_number_backfill', [
                            'outcome' => $dryRun ? 'resolved_dry_run' : 'backfilled',
                            'state_id' => (int) $state->id,
                            'conversation_id' => (int) $state->conversation_id,
                            'user_id' => (int) $state->user_id,
                            'wa_number_id' => (int) $resolution['wa_number_id'],
                            'matched_by' => (string) $resolution['matched_by'],
                            'message_id' => $resolution['message_id'] ?? null,
                        ]);
                    } elseif ($outcome === 'ambiguous') {
                        $stats['ambiguous']++;
                        if (count($ambiguousSample) < 10) {
                            $ambiguousSample[] = (int) $state->conversation_id;
                        }
                        Log::warning('communication.whatsapp.wa_number_backfill', [
                            'outcome' => 'ambiguous',
                            'state_id' => (int) $state->id,
                            'conversation_id' => (int) $state->conversation_id,
                            'user_id' => (int) $state->user_id,
                            'matched_by' => (string) $resolution['matched_by'],
                            'candidate_wa_number_ids' => $resolution['candidate_wa_number_ids'] ?? [],
                            'message_id' => $resolution['message_id'] ?? null,
                        ]);
                    } else {
                        $stats['unresolved']++;
                        if (count($unresolvedSample) < 10) {
                            $unresolvedSample[] = (int) $state->conversation_id;
                        }
                        Log::info('communication.whatsapp.wa_number_backfill', [
                            'outcome' => 'unresolved',
                            'state_id' => (int) $state->id,
                            'conversation_id' => (int) $state->conversation_id,
                            'user_id' => (int) $state->user_id,
                            'reason' => (string) ($resolution['reason'] ?? 'no_match'),
                            'message_id' => $resolution['message_id'] ?? null,
                        ]);
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['scanned', $stats['scanned']],
                ['updated', $stats['updated']],
                ['unresolved', $stats['unresolved']],
                ['ambiguous', $stats['ambiguous']],
            ]
        );

        if (! empty($unresolvedSample)) {
            $this->line('Sample unresolved conversation_ids: ' . implode(', ', $unresolvedSample));
        }
        if (! empty($ambiguousSample)) {
            $this->line('Sample ambiguous conversation_ids: ' . implode(', ', $ambiguousSample));
        }

        Log::info('communication.whatsapp.wa_number_backfill.summary', [
            'dry_run' => $dryRun,
            'scanned' => $stats['scanned'],
            'updated' => $stats['updated'],
            'unresolved' => $stats['unresolved'],
            'ambiguous' => $stats['ambiguous'],
            'sample_unresolved_conversation_ids' => $unresolvedSample,
            'sample_ambiguous_conversation_ids' => $ambiguousSample,
        ]);

        if ($dryRun) {
            $this->warn('Dry run completed. Re-run without --dry-run to apply updates.');
        }

        return self::SUCCESS;
    }

    /**
     * Strict priority resolver:
     * 1) meta.wa_number_id
     * 2) meta.display_phone / meta.display_phone_number
     * 3) meta.context.instance
     * 4) meta.channel_id
     */
    private function resolveWaNumberId(WaConversationState $state): array
    {
        $message = Message::query()
            ->where('conversation_id', $state->conversation_id)
            ->where('user_id', $state->user_id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first(['id', 'meta']);

        if (! $message) {
            return ['outcome' => 'unresolved', 'reason' => 'no_messages'];
        }

        $meta = is_array($message->meta) ? $message->meta : [];

        $directWaNumberId = isset($meta['wa_number_id']) ? (int) $meta['wa_number_id'] : null;
        if ($directWaNumberId !== null && $directWaNumberId > 0) {
            $candidateIds = WaNumber::query()
                ->where('id', $directWaNumberId)
                ->where('user_id', $state->user_id)
                ->pluck('id')
                ->all();

            if (count($candidateIds) === 1) {
                return [
                    'outcome' => 'resolved',
                    'wa_number_id' => (int) $candidateIds[0],
                    'matched_by' => 'meta.wa_number_id',
                    'message_id' => (int) $message->id,
                ];
            }
        }

        $displayPhone = $meta['display_phone']
            ?? $meta['display_phone_number']
            ?? data_get($meta, 'metadata.display_phone_number');
        if (is_string($displayPhone) && trim($displayPhone) !== '') {
            $normalizedPhone = $this->normalizePhone($displayPhone);
            $candidateIds = WaNumber::query()
                ->where('user_id', $state->user_id)
                ->where('phone_number', $normalizedPhone)
                ->pluck('id')
                ->all();

            if (count($candidateIds) === 1) {
                return [
                    'outcome' => 'resolved',
                    'wa_number_id' => (int) $candidateIds[0],
                    'matched_by' => 'meta.display_phone',
                    'message_id' => (int) $message->id,
                ];
            }
            if (count($candidateIds) > 1) {
                return [
                    'outcome' => 'ambiguous',
                    'matched_by' => 'meta.display_phone',
                    'candidate_wa_number_ids' => array_map('intval', $candidateIds),
                    'message_id' => (int) $message->id,
                ];
            }
        }

        $instance = data_get($meta, 'context.instance');
        if (is_string($instance) && trim($instance) !== '') {
            $candidateIds = WaNumber::query()
                ->where('user_id', $state->user_id)
                ->where('provider', 'evolution')
                ->where('provider_account_id', trim($instance))
                ->pluck('id')
                ->all();

            if (count($candidateIds) === 1) {
                return [
                    'outcome' => 'resolved',
                    'wa_number_id' => (int) $candidateIds[0],
                    'matched_by' => 'meta.context.instance',
                    'message_id' => (int) $message->id,
                ];
            }
            if (count($candidateIds) > 1) {
                return [
                    'outcome' => 'ambiguous',
                    'matched_by' => 'meta.context.instance',
                    'candidate_wa_number_ids' => array_map('intval', $candidateIds),
                    'message_id' => (int) $message->id,
                ];
            }
        }

        $channelId = $meta['channel_id'] ?? null;
        if ($channelId !== null && $channelId !== '') {
            $candidateIds = WaNumber::query()
                ->where('user_id', $state->user_id)
                ->where('marketing_channel_id', (int) $channelId)
                ->pluck('id')
                ->all();

            if (count($candidateIds) === 1) {
                return [
                    'outcome' => 'resolved',
                    'wa_number_id' => (int) $candidateIds[0],
                    'matched_by' => 'meta.channel_id',
                    'message_id' => (int) $message->id,
                ];
            }
            if (count($candidateIds) > 1) {
                return [
                    'outcome' => 'ambiguous',
                    'matched_by' => 'meta.channel_id',
                    'candidate_wa_number_ids' => array_map('intval', $candidateIds),
                    'message_id' => (int) $message->id,
                ];
            }
        }

        return [
            'outcome' => 'unresolved',
            'reason' => 'no_strict_match',
            'message_id' => (int) $message->id,
        ];
    }

    private function normalizePhone(string $value): string
    {
        $value = preg_replace('/[\s\-]+/', '', trim($value)) ?? '';
        if (preg_match('/^\+?\d+$/', $value) === 1) {
            $value = ltrim($value, '+');
            if ($value !== '' && $value[0] !== '0') {
                return '+' . $value;
            }
        }

        return $value;
    }
}

