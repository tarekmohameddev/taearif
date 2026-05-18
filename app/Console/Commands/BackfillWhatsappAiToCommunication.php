<?php

namespace App\Console\Commands;

use App\Domain\Communication\WhatsApp\Services\SyncWhatsappAiConversationToCommunicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\WhatsappAI\Entities\WhatsappConversation;

class BackfillWhatsappAiToCommunication extends Command
{
    protected $signature = 'whatsapp:backfill-ai-to-communication
                            {--user-id= : Limit backfill to one tenant user id}
                            {--conversation-id= : Limit backfill to one WhatsappAI conversation id}
                            {--since= : Only conversations updated on/after this date (Y-m-d)}
                            {--chunk=100 : Chunk size for conversation iteration}
                            {--dry-run : Count rows without writing}';

    protected $description = 'Backfill WhatsappAI conversations/messages into Communication v1 inbox tables';

    public function handle(SyncWhatsappAiConversationToCommunicationService $syncService): int
    {
        if (! Schema::hasTable('whatsapp_conversations') || ! Schema::hasTable('whatsapp_messages')) {
            $this->error('whatsapp_conversations and whatsapp_messages tables are required.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('conversations') || ! Schema::hasTable('messages') || ! Schema::hasTable('wa_conversation_states')) {
            $this->error('Communication v1 tables (conversations, messages, wa_conversation_states) are required.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $stats = [
            'conversations_scanned' => 0,
            'messages_scanned' => 0,
            'messages_synced' => 0,
            'messages_skipped_duplicate' => 0,
            'messages_failed' => 0,
            'unresolved_wa_number' => 0,
        ];

        $query = WhatsappConversation::query()->with(['messages' => function ($q) {
            $q->orderBy('created_at')->orderBy('id');
        }]);

        if ($this->option('user-id') !== null) {
            $query->where('user_id', (int) $this->option('user-id'));
        }

        if ($this->option('conversation-id') !== null) {
            $query->where('id', (int) $this->option('conversation-id'));
        }

        if ($this->option('since') !== null) {
            $since = Carbon::parse((string) $this->option('since'))->startOfDay();
            $query->where('updated_at', '>=', $since);
        }

        $this->info($dryRun ? 'Dry run: no writes will be performed.' : 'Starting backfill...');

        $query->orderBy('id')->chunkById($chunk, function ($conversations) use ($syncService, $dryRun, &$stats) {
            foreach ($conversations as $conversation) {
                $stats['conversations_scanned']++;

                foreach ($conversation->messages as $message) {
                    $stats['messages_scanned']++;

                    if ($dryRun) {
                        continue;
                    }

                    $beforeCount = \App\Models\Message::query()
                        ->where('user_id', (int) $conversation->user_id)
                        ->where('provider_message_id', $message->whatsapp_message_id)
                        ->count();

                    $result = $syncService->sync(
                        $conversation,
                        $message,
                        webhookMetadata: null,
                        incrementUnread: false,
                    );

                    if ($result === null) {
                        $stats['messages_failed']++;
                        continue;
                    }

                    if ($beforeCount > 0) {
                        $stats['messages_skipped_duplicate']++;
                    } else {
                        $stats['messages_synced']++;
                    }

                    $meta = is_array($result->meta) ? $result->meta : [];
                    if (($meta['wa_number_id'] ?? null) === null) {
                        $stats['unresolved_wa_number']++;
                    }
                }
            }
        });

        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($count, $key) => [$key, $count])->values()->all()
        );

        if ($dryRun) {
            $this->warn('Dry run complete. Re-run without --dry-run to apply changes.');
        } else {
            $this->info('Backfill complete.');
        }

        return self::SUCCESS;
    }
}
