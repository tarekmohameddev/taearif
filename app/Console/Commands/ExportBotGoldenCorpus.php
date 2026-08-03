<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BotFaqCandidate;
use App\Models\ShadowBotDraft;
use Illuminate\Console\Command;
use Modules\WhatsappAI\Entities\WhatsappConversation;

/**
 * Build a golden evaluation corpus by merging:
 *  1. Real WhatsApp conversations (historical customer messages)
 *  2. LLM-drafted ideal replies from bot_faq_candidates (auto-promoted)
 *  3. Real agent replies paired with bot shadow drafts
 */
final class ExportBotGoldenCorpus extends Command
{
    protected $signature = 'ai:export-golden-corpus
                            {--limit=500 : Max historical conversations to include}
                            {--output=storage/app/ai/golden-corpus-raw.json : Output file path}
                            {--include-faq-candidates : Merge approved/auto-promoted FAQ candidates as ideal replies}
                            {--include-agent-edits : Merge real agent replies from shadow_bot_drafts}';

    protected $description = 'Export and enrich golden corpus from conversations, LLM drafts, and real agent replies.';

    public function handle(): int
    {
        $limit       = (int) $this->option('limit');
        $output      = (string) $this->option('output');
        $includeFaq  = (bool) $this->option('include-faq-candidates');
        $includeEdits = (bool) $this->option('include-agent-edits');

        $this->info('Building golden corpus...');

        // ── 1. Historical conversations ──────────────────────────────────────
        $conversations = WhatsappConversation::with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->has('messages', '>=', 3)
            ->orderByDesc('message_count')
            ->limit($limit)
            ->get();

        $corpus           = [];
        $conversationIndex = [];

        foreach ($conversations as $conversation) {
            $turns = [];

            foreach ($conversation->messages as $msg) {
                $content    = (string) ($msg->content ?? '');
                $direction  = $msg->direction ?? 'inbound';
                $sourceMeta = is_array($msg->meta) ? $msg->meta : [];
                $source     = $sourceMeta['source'] ?? ($direction === 'outbound' ? 'agent' : 'customer');

                // Derive role from source so human-agent replies are not mislabeled as bot.
                // 'ai' source = bot-generated; any other outbound source = human agent.
                $role = match (true) {
                    $direction === 'inbound'  => 'customer',
                    $source === 'ai'          => 'bot',
                    default                   => 'agent',
                };

                $type = str_starts_with($content, '[صوتي:') ? 'voice_transcript' : 'text';

                $turns[] = [
                    'role'       => $role,
                    'source'     => $source,
                    'content'    => $content,
                    'type'       => $type,
                    'at'         => $msg->created_at?->toIso8601String(),
                    'message_id' => $msg->id,
                    'ideal_reply' => null,
                ];
            }

            $rawPhone = (string) ($conversation->customer_phone ?? '');
            $entry = [
                'conversation_id' => $conversation->id,
                'user_id'         => $conversation->user_id,
                'customer_phone'  => $this->maskPhone($rawPhone),
                'turns'           => $turns,
                'extracted_data'  => $conversation->extracted_data ?? null,
                'message_count'   => $conversation->message_count ?? count($turns),
                'ideal_reply'     => null,
            ];

            $corpus[]                               = $entry;
            $conversationIndex[$conversation->id]   = count($corpus) - 1;
        }

        // ── 2. Inject agent edits as ideal replies ───────────────────────────
        // ShadowBotDraft.conversation_id stores Communication-layer Conversation IDs,
        // which are in a different auto-increment sequence from WhatsappConversation IDs.
        // Cross-walking them is not reliable, so we append each paired draft as a
        // standalone corpus entry (same pattern as FAQ candidates) rather than attempting
        // a per-turn injection that will silently miss every record.
        if ($includeEdits) {
            $this->info('Merging real agent replies...');

            // 'agent_replied' is set by WebhookController::pairShadowDraft()
            $paired = ShadowBotDraft::whereNotNull('agent_reply')
                ->where('status', 'agent_replied')
                ->limit(2000)
                ->get();

            foreach ($paired as $draft) {
                $corpus[] = [
                    'conversation_id' => 'shadow_' . $draft->id,
                    'user_id'         => $draft->user_id,
                    'customer_phone'  => '***',
                    'turns'           => [
                        [
                            'role'         => 'bot',
                            'source'       => 'shadow_draft',
                            'content'      => (string) $draft->draft_reply,
                            'type'         => 'text',
                            'at'           => $draft->created_at?->toIso8601String(),
                            'message_id'   => null,
                            'ideal_reply'  => (string) $draft->agent_reply,
                            'ideal_source' => 'agent_edit',
                        ],
                    ],
                    'extracted_data' => ['confidence' => $draft->confidence, 'used_sources' => $draft->used_sources],
                    'message_count'  => 1,
                    'ideal_reply'    => (string) $draft->agent_reply,
                ];
            }
        }

        // ── 3. Inject LLM-drafted FAQ candidates ────────────────────────────
        if ($includeFaq) {
            $this->info('Merging LLM-drafted FAQ candidates...');
            $candidates = BotFaqCandidate::where('approval_status', 'auto_approved')
                ->limit(1000)
                ->get();

            foreach ($candidates as $faq) {
                // Synthesize a standalone corpus entry for each FAQ
                $corpus[] = [
                    'conversation_id' => 'faq_' . $faq->id,
                    'user_id'         => $faq->user_id,
                    'customer_phone'  => '***',
                    'turns'           => [
                        [
                            'role'         => 'customer',
                            'source'       => 'faq_candidate',
                            'content'      => $faq->question,
                            'type'         => 'text',
                            'at'           => null,
                            'message_id'   => null,
                            'ideal_reply'  => $faq->drafted_answer,
                            'ideal_source' => 'llm_drafted',
                        ],
                    ],
                    'extracted_data' => ['cluster_key' => $faq->cluster_key],
                    'message_count'  => 1,
                    'ideal_reply'    => $faq->drafted_answer,
                ];
            }
        }

        $outputPath = base_path($output);
        $dir = dirname($outputPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, json_encode($corpus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $withIdeal = count(array_filter($corpus, fn ($c) => $c['ideal_reply'] !== null));
        $this->info('Exported ' . count($corpus) . ' entries (' . $withIdeal . ' with ideal reply) to ' . $outputPath);

        return self::SUCCESS;
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return $phone;
        }

        $last4 = substr($phone, -4);
        $stars  = str_repeat('*', strlen($phone) - 4);

        return $stars . $last4;
    }
}
