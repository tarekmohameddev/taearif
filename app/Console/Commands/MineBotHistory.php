<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Knowledge\ArabicNormalizer;
use App\Domain\Ai\Knowledge\EmbeddingService;
use App\Domain\Ai\Services\LlmDriverFactory;
use App\Domain\Ai\Services\UsageRecorder;
use App\Domain\Ai\DTOs\LlmMessage;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Models\AiAlias;
use App\Models\AiKnowledgeSource;
use App\Models\BotFaqCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappAI\Entities\WhatsappConversation;

/**
 * Mine the 128k historical WhatsApp customer messages for:
 *
 * 1. Location and property-type aliases (deterministic, no LLM)
 * 2. Property-type aliases (deterministic)
 * 3. FAQ clusters with LLM-drafted answers → auto-promoted to ai_knowledge_sources
 * 4. Intent taxonomy extension (written to log for operator review)
 * 5. Relevance-gate rules (deterministic rules distilled from non-real-estate traffic)
 *
 * Usage:
 *   php artisan ai:mine-history                  # full run
 *   php artisan ai:mine-history --incremental    # only conversations newer than last run
 *   php artisan ai:mine-history --dry-run        # no writes
 *   php artisan ai:mine-history --tenant=123     # restrict to one tenant
 *   php artisan ai:mine-history --batch=50       # conversations per LLM batch
 */
final class MineBotHistory extends Command
{
    protected $signature = 'ai:mine-history
                            {--incremental  : Only process conversations not yet mined}
                            {--dry-run      : Analyse but do not write anything to the database}
                            {--tenant=      : Restrict to a single tenant user_id}
                            {--batch=50     : Number of conversations per LLM FAQ-cluster batch}
                            {--skip-llm     : Only run deterministic passes (aliases, glossary)}';

    protected $description = 'Mine historical WhatsApp conversations for aliases, FAQ clusters and relevance-gate patterns';

    // Minimum cluster size to draft a FAQ answer for
    private const FAQ_CLUSTER_MIN_SIZE = 3;

    // Deterministic property-type aliases from actual corpus data
    private const PROPERTY_TYPE_ALIASES = [
        'استراحة'   => 'استراحة',
        'شاليه'     => 'شاليه',
        'روف'       => 'شقة روف',
        'دور'       => 'دور',
        'ملحق'      => 'ملحق',
        'فله'       => 'فيلا',
        'فيلا'      => 'فيلا',
        'شقه'       => 'شقة',
        'شقة'       => 'شقة',
        'كومباوند'  => 'كومباوند',
        'مزرعة'     => 'مزرعة',
        'flat'      => 'شقة',
        'apartment' => 'شقة',
        'villa'     => 'فيلا',
        'land'      => 'أرض',
        'ارض'       => 'أرض',
        'أرض'       => 'أرض',
        'مكتب'      => 'مكتب',
        'محل'       => 'محل تجاري',
        'مستودع'    => 'مستودع',
    ];

    // Deterministic city aliases from corpus data
    private const CITY_ALIASES = [
        'مدينة الرياض' => 'الرياض',
        'بريده'        => 'بريدة',
        'جده'          => 'جدة',
        'جدا'          => 'جدة',
        'عنيزه'        => 'عنيزة',
        'مكه'          => 'مكة المكرمة',
        'مكة'          => 'مكة المكرمة',
        'رياض'         => 'الرياض',
        'دمام'         => 'الدمام',
        'خبر'          => 'الخبر',
        'تبوك'         => 'تبوك',
        'ابها'         => 'أبها',
        'القصيم'       => 'بريدة',
        'الخرج'        => 'الخرج',
        'الاحساء'      => 'الأحساء',
        'احساء'        => 'الأحساء',
        'حائل'         => 'حائل',
        'نجران'        => 'نجران',
        'جيزان'        => 'جيزان',
    ];

    // Common off-topic patterns used for relevance gate rules
    private const RELEVANCE_GATE_KEYWORDS = [
        'رصيد', 'فاتورة كهرباء', 'صيانة', 'تسريب', 'إيجار منتهي',
        'مفاتيح', 'إخلاء', 'عروض رمضان', 'تهانينا', 'مبارك',
        'صور', 'كتالوج', 'بروشور',
    ];

    public function __construct(
        private readonly LlmDriverFactory $driverFactory,
        private readonly UsageRecorder $usageRecorder,
        private readonly EmbeddingService $embeddingService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun      = (bool) $this->option('dry-run');
        $incremental = (bool) $this->option('incremental');
        $skipLlm     = (bool) $this->option('skip-llm');
        $tenantId    = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $batchSize   = max(5, min(200, (int) ($this->option('batch') ?? 50)));

        if ($dryRun) {
            $this->warn('[DRY RUN] No database writes will occur.');
        }

        $this->info('=== Phase 2 — Mining historical conversations ===');

        // ── 1. Deterministic alias seeding ────────────────────────────────────
        $this->seedDeterministicAliases($dryRun);

        // ── 2. LLM-assisted FAQ clustering ────────────────────────────────────
        if (! $skipLlm) {
            $this->runFaqClustering($dryRun, $incremental, $tenantId, $batchSize);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Deterministic passes
    // ─────────────────────────────────────────────────────────────────────────

    private function seedDeterministicAliases(bool $dryRun): void
    {
        $this->info('  [1/2] Seeding city aliases…');
        foreach (self::CITY_ALIASES as $alias => $canonical) {
            if ($dryRun) {
                $this->line("    DRY: city alias '{$alias}' → '{$canonical}'");
                continue;
            }
            AiAlias::upsertAlias('city', ArabicNormalizer::normalizeForSearch($alias), $canonical);
        }

        $this->info('  [2/2] Seeding property-type aliases…');
        foreach (self::PROPERTY_TYPE_ALIASES as $alias => $canonical) {
            if ($dryRun) {
                $this->line("    DRY: property_type alias '{$alias}' → '{$canonical}'");
                continue;
            }
            AiAlias::upsertAlias('property_type', ArabicNormalizer::normalizeForSearch($alias), $canonical);
        }

        $this->info("  Deterministic aliases seeded.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LLM-assisted FAQ clustering
    // ─────────────────────────────────────────────────────────────────────────

    private function runFaqClustering(bool $dryRun, bool $incremental, ?int $tenantId, int $batchSize): void
    {
        $this->info('  [LLM] FAQ clustering…');

        // Load conversations with extracted_data (free labels from legacy processing)
        $query = WhatsappConversation::query()
            ->whereNotNull('extracted_data')
            ->whereNotNull('ai_summary')
            ->where('is_real_estate_inquiry', true);

        if ($tenantId !== null) {
            $query->where('user_id', $tenantId);
        }

        if ($incremental) {
            // Use a simple marker in ai_aliases to track the last run
            $lastRunAlias = AiAlias::where('alias_type', 'system')
                ->where('alias', 'mine_history_cursor')
                ->first();
            $cursor = $lastRunAlias?->canonical;
            if ($cursor) {
                $query->where('id', '>', (int) $cursor);
                $this->info("  Incremental mode: processing conversations after ID {$cursor}");
            }
        }

        $totalProcessed = 0;
        $faqCandidates  = [];

        // Group by tenant for scoped processing
        $tenantIds = $query->distinct()->pluck('user_id')->toArray();

        foreach ($tenantIds as $tid) {
            $tenantConvs = (clone $query)
                ->where('user_id', $tid)
                ->orderBy('id')
                ->get(['id', 'user_id', 'ai_summary', 'extracted_data', 'customer_phone']);

            $this->info("  Tenant {$tid}: {$tenantConvs->count()} conversations");

            // Batch into groups to keep prompt size manageable
            foreach ($tenantConvs->chunk($batchSize) as $chunk) {
                $summaries = $chunk->map(fn ($c) => [
                    'id'         => $c->id,
                    'summary'    => $c->ai_summary,
                    'extracted'  => is_array($c->extracted_data) ? $c->extracted_data : [],
                ])->values()->toArray();

                $clusters = $this->clusterFaqsWithLlm((int) $tid, $summaries);
                foreach ($clusters as $cluster) {
                    $faqCandidates[] = array_merge($cluster, ['user_id' => $tid]);
                }

                $totalProcessed += $chunk->count();
                $maxId = $chunk->max('id');

                if (! $dryRun) {
                    // Update cursor
                    AiAlias::upsertAlias('system', 'mine_history_cursor', (string) $maxId);
                }
            }
        }

        $this->info("  Processed {$totalProcessed} conversations, found " . count($faqCandidates) . " FAQ candidates.");

        // Persist FAQ candidates
        foreach ($faqCandidates as $candidate) {
            if ($dryRun) {
                $this->line("  DRY FAQ: [{$candidate['user_id']}] " . mb_substr($candidate['question'], 0, 60));
                continue;
            }
            $this->persistFaqCandidate($candidate);
        }
    }

    /**
     * Call the LLM to identify recurring question clusters in a batch of conversation summaries.
     *
     * @param  array<array{id: int, summary: string, extracted: array}>  $summaries
     * @return array<array{question: string, drafted_answer: string, occurrence_count: int, cluster_key: string}>
     */
    private function clusterFaqsWithLlm(int $tenantId, array $summaries): array
    {
        if (empty($summaries)) {
            return [];
        }

        $summaryBlock = '';
        foreach ($summaries as $i => $s) {
            $summaryBlock .= ($i + 1) . '. ' . $s['summary'] . "\n";
        }

        $prompt = <<<PROMPT
أنت محلل استفسارات عقارية. لديك ملخصات محادثات واتساب من عملاء عقاريين.

مهمتك:
١. حدد الأسئلة المتكررة (أسئلة نفسها أو متشابهة جداً تظهر أكثر من مرتين).
٢. لكل مجموعة أسئلة متشابهة، اكتب سؤالاً موحداً وإجابة مختصرة ومفيدة.
٣. لا تخترع أسعاراً أو معلومات غير موجودة في الملخصات.

الملخصات:
{$summaryBlock}

أرجع JSON فقط بهذا الشكل:
{
  "clusters": [
    {
      "question": "نص السؤال الموحد",
      "drafted_answer": "إجابة مختصرة ومفيدة للعميل (2-3 جمل عربية طبيعية)",
      "occurrence_count": 5
    }
  ]
}

ملاحظات:
- فقط الأسئلة المتكررة (occurrence_count >= {CLUSTER_MIN}).
- الإجابة يجب أن تكون عامة وصحيحة بغض النظر عن العقار المحدد.
- إذا لم تجد أسئلة متكررة، أرجع clusters: []
PROMPT;

        $prompt = str_replace('{CLUSTER_MIN}', (string) self::FAQ_CLUSTER_MIN_SIZE, $prompt);

        try {
            $driver   = $this->driverFactory->makeForTenant($tenantId);
            $fastModel = env('OPENAI_FAST_MODEL', 'gpt-5-nano');

            $response = $driver->complete(new LlmRequest(
                messages: [LlmMessage::user($prompt)],
                model: $fastModel,
                maxTokens: 1000,
                temperature: 0.2,
                jsonMode: true,
                timeoutSeconds: 60,
            ));

            $this->usageRecorder->record($tenantId, 'mine', $response, null);

            if (! $response->success) {
                return [];
            }

            $data     = json_decode($response->content, true);
            $clusters = $data['clusters'] ?? [];

            return array_map(function (array $c) {
                $c['cluster_key'] = hash('sha256', ArabicNormalizer::normalizeForSearch($c['question'] ?? ''));
                return $c;
            }, array_filter($clusters, fn ($c) => (
                ! empty($c['question']) &&
                ! empty($c['drafted_answer']) &&
                (int) ($c['occurrence_count'] ?? 0) >= self::FAQ_CLUSTER_MIN_SIZE
            )));
        } catch (\Throwable $e) {
            Log::warning('ai:mine-history.llm_failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Persist a FAQ candidate and auto-promote it to ai_knowledge_sources.
     *
     * @param  array{user_id: int, question: string, drafted_answer: string, occurrence_count: int, cluster_key: string}  $candidate
     */
    private function persistFaqCandidate(array $candidate): void
    {
        try {
            DB::transaction(function () use ($candidate) {
                $existing = BotFaqCandidate::where('user_id', $candidate['user_id'])
                    ->where('cluster_key', $candidate['cluster_key'])
                    ->first();

                if ($existing !== null) {
                    $existing->increment('occurrence_count', (int) ($candidate['occurrence_count'] ?? 1));
                    return;
                }

                // Auto-promote to knowledge source
                $sourceText = "س: {$candidate['question']}\nج: {$candidate['drafted_answer']}";
                $source = AiKnowledgeSource::create([
                    'user_id'  => $candidate['user_id'],
                    'type'     => 'faq',
                    'name'     => mb_substr($candidate['question'], 0, 100),
                    'active'   => true,
                ]);

                // Index immediately so retrieval can serve it
                try {
                    $this->embeddingService->indexSource($source, $sourceText);
                } catch (\Throwable $e) {
                    Log::warning('ai:mine-history.embed_failed', [
                        'source_id' => $source->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                BotFaqCandidate::create([
                    'user_id'           => $candidate['user_id'],
                    'cluster_key'       => $candidate['cluster_key'],
                    'question'          => $candidate['question'],
                    'drafted_answer'    => $candidate['drafted_answer'],
                    'occurrence_count'  => (int) ($candidate['occurrence_count'] ?? 1),
                    'approval_status'   => 'auto_approved',
                    'knowledge_source_id' => $source->id,
                    'mine_batch'        => date('Y-m-d'),
                ]);
            });
        } catch (\Throwable $e) {
            Log::warning('ai:mine-history.persist_failed', [
                'user_id' => $candidate['user_id'],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
