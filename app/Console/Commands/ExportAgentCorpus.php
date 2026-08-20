<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Agent\Transport\RecordingTransport;
use App\Models\AiTurnTrace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\WhatsappAI\Entities\WhatsappConversation;

/**
 * Export replayable agent corpus fixtures from real WhatsApp conversations.
 *
 * Each fixture is a JSON file containing:
 *   - conversation metadata (tenant, turns)
 *   - the full turn transcript (messages)
 *   - expected hard invariants (schema only; actual values come from replay)
 *
 * Seeded with:
 *   - 150–200 real conversations from whatsapp_conversations (prioritise those with
 *     shadow_bot_drafts or ai_turn_traces so we have real bot behaviour)
 *   - sandbox_round3 / sandbox_round4 scripts from storage/app/
 *   - One locked case per bug in the known-issues doc (see $bugsCorpus below)
 */
final class ExportAgentCorpus extends Command
{
    protected $signature = 'ai:agent:export-corpus
        {--limit=200 : Max real conversations to include}
        {--output=tests/Fixtures/agent/corpus : Output directory}
        {--tenant= : Filter to a specific tenant user_id}';

    protected $description = 'Export replayable corpus fixtures for the agent test suite.';

    private const KNOWN_BUG_CASES = [
        [
            'id'          => 'bug_01_greeting_fails',
            'description' => 'Greeting should not increment _failed_turns',
            'turns'       => [
                ['role' => 'customer', 'text' => 'السلام عليكم'],
            ],
            'invariants'  => ['no_availability_claim_without_search'],
        ],
        [
            'id'          => 'bug_02_bedrooms_building',
            'description' => 'Slot fill should not ask bedrooms for building/land',
            'turns'       => [
                ['role' => 'customer', 'text' => 'عندك عمارات للبيع؟'],
            ],
            'invariants'  => ['no_bedroom_question_for_building'],
        ],
        [
            'id'          => 'bug_05_no_bare_prices',
            'description' => 'Bot must not type bare price numbers; must use placeholders',
            'turns'       => [
                ['role' => 'customer', 'text' => 'ابي شقة في الرياض بميزانية 500 ألف'],
            ],
            'invariants'  => ['no_bare_number_in_reply'],
        ],
        [
            'id'          => 'bug_07_search_runs',
            'description' => 'search_inventory must be called at least once on a property search turn',
            'turns'       => [
                ['role' => 'customer', 'text' => 'وش عندك من فلل في جدة؟'],
            ],
            'invariants'  => ['search_tool_was_called'],
        ],
        [
            'id'          => 'bug_10_zero_results_honest',
            'description' => 'Must not claim availability when search returns 0 results',
            'turns'       => [
                ['role' => 'customer', 'text' => 'ابي شقة في حي الفيصلية بالرياض بـ 200 ألف إيجار'],
            ],
            'invariants'  => ['no_availability_claim_on_empty_ledger'],
        ],
        [
            'id'          => 'bug_15_escalate_on_human_request',
            'description' => 'Must escalate when customer explicitly requests a human',
            'turns'       => [
                ['role' => 'customer', 'text' => 'ابغى أتكلم مع موظف حقيقي'],
            ],
            'invariants'  => ['escalation_fired'],
        ],
        [
            'id'          => 'bug_22_brief_preserved',
            'description' => 'Brief must not lose city after second turn',
            'turns'       => [
                ['role' => 'customer', 'text' => 'ابي شقة في الرياض'],
                ['role' => 'bot',      'text' => 'ممتاز! ما هو الحد الأقصى لميزانيتك؟'],
                ['role' => 'customer', 'text' => '500 ألف'],
            ],
            'invariants'  => ['brief_city_preserved', 'no_bare_number_in_reply'],
        ],
        [
            'id'          => 'bug_33_opt_out_works',
            'description' => 'Bot must stop responding after opt-out keyword',
            'turns'       => [
                ['role' => 'customer', 'text' => 'إيقاف'],
            ],
            'invariants'  => ['bot_skipped_or_opted_out'],
        ],
    ];

    public function handle(): int
    {
        $limit    = (int) $this->option('limit');
        $output   = (string) $this->option('output');
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;

        if (!is_dir($output)) {
            mkdir($output, 0755, true);
        }

        $count = 0;

        // ── 1. Known bug cases ─────────────────────────────────────────────────
        $this->info('Writing known-bug locked corpus cases...');
        foreach (self::KNOWN_BUG_CASES as $bug) {
            $this->writeFixture("{$output}/bugs", $bug['id'], [
                'type'         => 'bug_regression',
                'id'           => $bug['id'],
                'description'  => $bug['description'],
                'turns'        => $bug['turns'],
                'invariants'   => $bug['invariants'],
            ]);
            $count++;
        }

        // ── 2. Sandbox round3/round4 scripts ───────────────────────────────────
        $this->info('Importing sandbox round scripts...');
        $sandboxFiles = [
            storage_path('app/sandbox_round3_results.json'),
            storage_path('app/sandbox_round4_results.json'),
        ];
        foreach ($sandboxFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }
            $data = json_decode(file_get_contents($file) ?: '', true);
            if (!is_array($data)) {
                continue;
            }
            $rounds = $data['rounds'] ?? $data;
            foreach ($rounds as $i => $round) {
                $turns = $round['turns'] ?? [];
                if (empty($turns)) {
                    continue;
                }
                $id = basename($file, '.json') . '_' . $i;
                $this->writeFixture("{$output}/sandbox", $id, [
                    'type'      => 'sandbox',
                    'id'        => $id,
                    'turns'     => $turns,
                    'invariants'=> ['no_bare_number_in_reply', 'no_availability_claim_on_empty_ledger'],
                ]);
                $count++;
            }
        }

        // ── 3. Real conversations from whatsapp_conversations ─────────────────
        $this->info('Exporting real conversations...');
        $query = WhatsappConversation::with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->has('messages', '>=', 4);

        if ($tenantId !== null) {
            $query->where('user_id', $tenantId);
        }

        $conversations = $query
            ->orderByDesc('message_count')
            ->limit($limit)
            ->get();

        foreach ($conversations as $conversation) {
            $turns = [];
            foreach ($conversation->messages as $msg) {
                $direction = (string) ($msg->direction ?? 'inbound');
                $meta      = is_array($msg->meta) ? $msg->meta : [];
                $source    = (string) ($meta['source'] ?? '');
                $role      = match (true) {
                    $direction === 'inbound'         => 'customer',
                    $source === 'ai'                 => 'bot',
                    default                          => 'agent',
                };
                $turns[] = ['role' => $role, 'text' => (string) ($msg->content ?? '')];
            }

            if (empty($turns)) {
                continue;
            }

            $id = 'conv_' . $conversation->id;
            $this->writeFixture("{$output}/real", $id, [
                'type'       => 'real_conversation',
                'id'         => $id,
                'tenant_id'  => $conversation->user_id,
                'turns'      => $turns,
                'invariants' => ['no_bare_number_in_reply', 'no_availability_claim_on_empty_ledger'],
            ]);
            $count++;
        }

        $this->info("Corpus exported: {$count} fixtures → {$output}");
        return Command::SUCCESS;
    }

    private function writeFixture(string $dir, string $id, array $data): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = "{$dir}/{$id}.json";
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
