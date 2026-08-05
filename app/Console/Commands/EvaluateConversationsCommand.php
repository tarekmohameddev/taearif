<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communication\WhatsApp\Bot\SandboxService;
use Illuminate\Console\Command;

/**
 * ai:agent:eval-conversations — live sandbox evaluation against a corpus of real conversations.
 *
 * Promotes the throwaway storage/app/_eval_*.php scripts into a repeatable artisan command.
 *
 * Usage:
 *   php artisan ai:agent:eval-conversations
 *   php artisan ai:agent:eval-conversations --tenant=1430 --wa-number=2 --limit=10
 *   php artisan ai:agent:eval-conversations --convs=storage/app/_eval_convs.json --no-report
 *
 * The command runs through each conversation in the JSON corpus, replays the inbound messages
 * in sequence via the SandboxService (full bot pipeline, dry-run mode), records outcomes, and
 * then judges each conversation against category-specific rubrics to produce a markdown report.
 */
final class EvaluateConversationsCommand extends Command
{
    protected $signature = 'ai:agent:eval-conversations
        {--convs=storage/app/_eval_convs.json    : Path to the JSON conversations corpus}
        {--output=storage/app/_eval_results.json : Path to write per-conversation result JSON}
        {--report-path=docs/                     : Directory to write the markdown report}
        {--tenant=1430                           : Tenant user_id (wa_number must belong to this tenant)}
        {--wa-number=2                           : wa_number_id to simulate}
        {--limit=                                : Process only this many conversations (for quick tests)}
        {--no-report                             : Skip markdown report generation}
        {--checkpoint=10                         : Checkpoint every N conversations (0 = disable)}';

    protected $description = 'Run a live sandbox evaluation against a JSON conversation corpus and generate a judge report.';

    public function __construct(
        private readonly SandboxService $sandbox,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $convsPath  = (string) $this->option('convs');
        $outputPath = (string) $this->option('output');
        $tenantId   = (int)    $this->option('tenant');
        $waNumberId = (int)    $this->option('wa-number');
        $limit      = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $checkpoint = (int) $this->option('checkpoint');
        $noReport   = (bool)   $this->option('no-report');

        if (!file_exists($convsPath)) {
            $this->error("Conversations file not found: {$convsPath}");
            return Command::FAILURE;
        }

        $allConvs = json_decode((string) file_get_contents($convsPath), true);
        if (!is_array($allConvs)) {
            $this->error("Failed to parse conversations JSON from {$convsPath}");
            return Command::FAILURE;
        }

        $convs = $limit !== null ? array_slice($allConvs, 0, $limit) : $allConvs;
        $total = count($convs);

        $this->info("Tenant={$tenantId} wa_number={$waNumberId} | Processing {$total} conversations…");
        $this->newLine();

        $results = [];

        foreach ($convs as $idx => $conv) {
            $phone = sprintf('+96650%07d', $idx + 1);
            $num   = $idx + 1;
            $cat   = $conv['category'] ?? 'unknown';

            $this->line("[{$num}/{$total}] wa_conv={$conv['wa_conv_id']} [{$cat}] phone={$phone}");
            $this->line('  Trigger: ' . mb_substr((string) ($conv['trigger_msg'] ?? ''), 0, 80));

            $this->sandbox->reset($tenantId, $waNumberId, $phone);

            $transcript = $conv['transcript'] ?? [];
            $turns      = [];
            $lastError  = null;

            // Replay last 5 inbound messages for context
            $inboundMsgs = array_values(array_filter(
                $transcript,
                fn ($m) => ($m['direction'] ?? 'inbound') === 'inbound',
            ));
            $inboundMsgs = array_slice($inboundMsgs, -5);

            foreach ($inboundMsgs as $tIdx => $msg) {
                $text = trim((string) ($msg['content'] ?? ''));

                if ($text === '' || $text === '.') {
                    $turns[] = [
                        'turn'    => $tIdx + 1,
                        'input'   => $text,
                        'skipped' => true,
                        'reason'  => 'empty or dot-only message',
                    ];
                    continue;
                }

                $startMs = (int) round(microtime(true) * 1000);

                try {
                    $result  = $this->sandbox->runTurn($tenantId, $waNumberId, $phone, $text);
                    $elapsed = (int) round(microtime(true) * 1000) - $startMs;

                    $turns[] = [
                        'turn'           => $tIdx + 1,
                        'input'          => mb_substr($text, 0, 200),
                        'outcome'        => $result['outcome']        ?? 'unknown',
                        'reason'         => $result['reason']         ?? null,
                        'reply'          => mb_substr((string) ($result['reply'] ?? ''), 0, 400),
                        'needs_human'    => $result['needs_human']    ?? false,
                        'facts'          => $result['facts']          ?? null,
                        'opt_out_status' => $result['opt_out_status'] ?? null,
                        'elapsed_ms'     => $elapsed,
                    ];

                    $outcome = $result['outcome'] ?? '?';
                    $reply   = mb_substr((string) ($result['reply'] ?? ''), 0, 70);
                    $this->line("    turn " . ($tIdx + 1) . " → outcome={$outcome} ({$elapsed}ms) reply=\"{$reply}…\"");
                } catch (\Throwable $e) {
                    $elapsed = (int) round(microtime(true) * 1000) - $startMs;
                    $turns[] = [
                        'turn'       => $tIdx + 1,
                        'input'      => mb_substr($text, 0, 200),
                        'outcome'    => 'php_error',
                        'reason'     => $e->getMessage(),
                        'reply'      => null,
                        'elapsed_ms' => $elapsed,
                    ];
                    $lastError = $e->getMessage();
                    $this->line("    turn " . ($tIdx + 1) . ' → ERROR: ' . mb_substr($e->getMessage(), 0, 80));
                }
            }

            $finalTurn = collect($turns)->last(fn ($t) => !($t['skipped'] ?? false));

            $results[] = [
                'idx'           => $num,
                'wa_conv_id'    => $conv['wa_conv_id'],
                'category'      => $cat,
                're_score'      => $conv['re_score'] ?? null,
                'phone'         => $phone,
                'turns'         => $turns,
                'final_outcome' => $finalTurn['outcome'] ?? 'no_turns',
                'final_reason'  => $finalTurn['reason']  ?? null,
                'final_reply'   => $finalTurn['reply']   ?? null,
                'final_facts'   => $finalTurn['facts']   ?? null,
                'trigger_msg'   => mb_substr((string) ($conv['trigger_msg'] ?? ''), 0, 200),
                'transcript'    => array_map(
                    fn ($m) => ['d' => $m['direction'], 'c' => mb_substr($m['content'], 0, 150)],
                    $transcript,
                ),
                'turn_count'    => count(array_filter($turns, fn ($t) => !($t['skipped'] ?? false))),
                'error'         => $lastError,
            ];

            $this->newLine();

            if ($checkpoint > 0 && $num % $checkpoint === 0) {
                file_put_contents(
                    $outputPath,
                    json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                );
                $this->comment("  >>> Checkpoint saved ({$num}/{$total}) <<<");
            }
        }

        // Final save
        file_put_contents(
            $outputPath,
            json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );

        $this->line('====================================================');
        $this->info("DONE. {$total} conversations processed. Results saved to {$outputPath}");

        $outcomes = array_count_values(array_column($results, 'final_outcome'));
        foreach ($outcomes as $o => $c) {
            $this->line("  {$o}: {$c}");
        }

        if (!$noReport) {
            $this->buildReport($results, (string) $this->option('report-path'));
        }

        return Command::SUCCESS;
    }

    // ─── Report generation ────────────────────────────────────────────────────

    private function buildReport(array $results, string $reportDir): void
    {
        $total  = count($results);
        $stats  = ['PASS' => 0, 'WARN' => 0, 'FAIL' => 0, 'SKIP' => 0];
        $judged = [];

        foreach ($results as $r) {
            $j = $this->judgeConversation($r);
            $judged[] = $j;
            $stats[$j['rating']]++;
        }

        $lines   = [];
        $lines[] = '# AI Bot Evaluation Report — ' . $total . ' Real Conversations';
        $lines[] = '';
        $lines[] = '> **Date:** ' . date('Y-m-d H:i') . '  ';
        $lines[] = '> **Conversations evaluated:** ' . $total;
        $lines[] = '';

        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---|';
        $lines[] = '| Total | ' . $total . ' |';
        $lines[] = '| Pass (✅) | ' . $stats['PASS'] . ' (' . ($total > 0 ? round($stats['PASS'] / $total * 100) : 0) . '%) |';
        $lines[] = '| Warn (⚠️) | ' . $stats['WARN'] . ' (' . ($total > 0 ? round($stats['WARN'] / $total * 100) : 0) . '%) |';
        $lines[] = '| Fail (❌) | ' . $stats['FAIL'] . ' (' . ($total > 0 ? round($stats['FAIL'] / $total * 100) : 0) . '%) |';
        $lines[] = '| Skip (🔁) | ' . $stats['SKIP'] . ' (' . ($total > 0 ? round($stats['SKIP'] / $total * 100) : 0) . '%) |';
        $lines[] = '';

        // Outcome breakdown
        $outcomeStats = [];
        foreach ($results as $r) {
            $oc = $r['final_outcome'] ?? 'unknown';
            $outcomeStats[$oc] = ($outcomeStats[$oc] ?? 0) + 1;
        }
        arsort($outcomeStats);
        $lines[] = '### Bot Outcomes';
        $lines[] = '';
        $lines[] = '| Outcome | Count |';
        $lines[] = '|---|---|';
        foreach ($outcomeStats as $oc => $cnt) {
            $lines[] = "| {$oc} | {$cnt} |";
        }
        $lines[] = '';

        // Category breakdown
        $ratingByCat = [];
        foreach ($judged as $j) {
            $cat    = $j['category'];
            $rating = $j['rating'];
            $ratingByCat[$cat][$rating] = ($ratingByCat[$cat][$rating] ?? 0) + 1;
        }
        $lines[] = '### Ratings by Category';
        $lines[] = '';
        $lines[] = '| Category | Count | PASS | WARN | FAIL |';
        $lines[] = '|---|---|---|---|---|';
        foreach ($ratingByCat as $cat => $ratings) {
            $cnt  = array_sum($ratings);
            $pass = $ratings['PASS'] ?? 0;
            $warn = $ratings['WARN'] ?? 0;
            $fail = $ratings['FAIL'] ?? 0;
            $lines[] = "| {$cat} | {$cnt} | {$pass} | {$warn} | {$fail} |";
        }
        $lines[] = '';

        // Issues aggregation
        $allIssues = [];
        foreach ($judged as $j) {
            foreach ($j['issues'] as $issue) {
                $key = preg_replace('/\d+/', 'N', $issue);
                $allIssues[$key] = ($allIssues[$key] ?? 0) + 1;
            }
        }
        if ($allIssues) {
            arsort($allIssues);
            $lines[] = '### Top Issues';
            $lines[] = '';
            $lines[] = '| Issue | Count |';
            $lines[] = '|---|---|';
            foreach (array_slice($allIssues, 0, 10, true) as $issue => $cnt) {
                $lines[] = '| ' . str_replace('|', '\\|', $issue) . " | {$cnt} |";
            }
            $lines[] = '';
        }

        // Per-conversation detail
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '## Detailed Analysis';
        $lines[] = '';
        $icons    = ['PASS' => '✅', 'WARN' => '⚠️', 'FAIL' => '❌', 'SKIP' => '🔁'];

        foreach ($results as $i => $r) {
            $j    = $judged[$i];
            $icon = $icons[$j['rating']] ?? '❓';
            $num  = $r['idx'];
            $cid  = $r['wa_conv_id'];

            $lines[] = "### {$icon} #{$num} — Conv {$cid} [{$j['category']}] final={$r['final_outcome']}";
            $lines[] = '';
            $lines[] = '**Trigger:** ' . mb_substr((string) ($r['trigger_msg'] ?? ''), 0, 200);
            $lines[] = '';

            foreach ($r['turns'] as $t) {
                if ($t['skipped'] ?? false) {
                    $lines[] = "**Turn {$t['turn']}:** Skipped ({$t['reason']})";
                } else {
                    $outcome = $t['outcome'] ?? '?';
                    $elapsed = $t['elapsed_ms'] ?? 0;
                    $reply   = mb_substr((string) ($t['reply'] ?? ''), 0, 300);
                    $reason  = $t['reason'] ? " (reason: {$t['reason']})" : '';
                    $lines[] = "**Turn {$t['turn']}:** `{$outcome}`{$reason} — {$elapsed}ms";
                    if ($reply) {
                        $lines[] = '> ' . str_replace("\n", "  \n> ", $reply);
                    }
                }
                $lines[] = '';
            }

            $lines[] = "**Verdict:** {$icon} **{$j['rating']}**";
            if (!empty($j['issues'])) {
                foreach ($j['issues'] as $issue) {
                    $lines[] = "- ⚠ {$issue}";
                }
            }
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
        }

        // Fix priority list
        $p1 = [];
        $p2 = [];
        foreach ($results as $i => $r) {
            $j = $judged[$i];
            if ($j['rating'] === 'FAIL') {
                foreach ($j['optimal'] as $fix) {
                    $p1[] = "Conv #{$r['idx']} (wa_conv={$r['wa_conv_id']}): {$fix}";
                }
            } elseif ($j['rating'] === 'WARN') {
                foreach ($j['optimal'] as $fix) {
                    $p2[] = "Conv #{$r['idx']} (wa_conv={$r['wa_conv_id']}): {$fix}";
                }
            }
        }

        if ($p1) {
            $lines[] = '## 🔴 P1 — Must Fix (FAIL cases)';
            $lines[] = '';
            foreach (array_unique($p1) as $fix) {
                $lines[] = "- {$fix}";
            }
            $lines[] = '';
        }
        if ($p2) {
            $lines[] = '## 🟡 P2 — Should Fix (WARN cases)';
            $lines[] = '';
            foreach (array_unique($p2) as $fix) {
                $lines[] = "- {$fix}";
            }
            $lines[] = '';
        }

        $lines[] = '---';
        $lines[] = '_Report generated by `ai:agent:eval-conversations` — ' . date('Y-m-d H:i:s') . '_';

        $reportPath = rtrim($reportDir, '/\\') . '/bot-eval-report-' . count($results) . '-convs.md';
        file_put_contents($reportPath, implode("\n", $lines));
        $this->info("Report written to {$reportPath}");
    }

    // ─── Judge helpers ────────────────────────────────────────────────────────

    /** @return array{rating: string, issues: list<string>, optimal: list<string>, category: string} */
    private function judgeConversation(array $r): array
    {
        $outcome = $r['final_outcome'] ?? 'unknown';
        $reply   = (string) ($r['final_reply'] ?? '');
        $reason  = (string) ($r['final_reason'] ?? '');
        $trigger = (string) ($r['trigger_msg'] ?? '');
        $turns   = $r['turns'] ?? [];

        $category   = $this->detectCategory($trigger, $turns);
        $turnIssues = $this->findWorstTurnIssues($turns);

        $rating  = 'PASS';
        $issues  = array_merge([], $turnIssues);
        $optimal = [];

        if ($outcome === 'php_error' || ($r['turn_count'] ?? 0) === 0) {
            return [
                'rating'   => $outcome === 'php_error' ? 'FAIL' : 'SKIP',
                'issues'   => $outcome === 'php_error'
                    ? ['PHP exception: ' . mb_substr((string) ($r['error'] ?? ''), 0, 120)]
                    : ['No valid turns executed'],
                'optimal'  => ['Fix the exception or provide valid messages'],
                'category' => $category,
            ];
        }

        switch ($category) {
            case 'seller':
                if ($outcome === 'delivered') {
                    $optimal[] = 'Bot correctly declined to list the property';
                } elseif ($outcome === 'handoff' && $reason === 'citation_violation') {
                    $rating    = 'FAIL';
                    $issues[]  = 'citation_violation while handling a seller — bot echoed a number from customer\'s message';
                    $optimal[] = 'Bot should NOT echo customer-supplied numbers in its own reply';
                } else {
                    $rating    = 'WARN';
                    $issues[]  = "Bot handed off a seller conversation (reason={$reason}) instead of politely declining";
                    $optimal[] = 'Teach the bot to handle sellers gracefully';
                }
                break;

            case 'greeting':
                if ($outcome === 'delivered' && mb_strlen(trim($reply)) > 10) {
                    $optimal[] = 'Bot responded appropriately to greeting';
                } elseif ($outcome === 'delivered') {
                    $rating    = 'WARN';
                    $issues[]  = 'Reply to greeting is too short';
                    $optimal[] = 'Extend the greeting reply to include a helpful prompt';
                } else {
                    $rating    = 'FAIL';
                    $issues[]  = 'Bot handed off on a greeting — should never escalate on a simple greeting';
                    $optimal[] = 'Greeting detection should fire before EscalateToHumanTool is considered';
                }
                break;

            case 'portal_lead':
                if ($outcome === 'handoff') {
                    $rating    = 'FAIL';
                    $issues[]  = "Portal lead was escalated to human (reason={$reason}) — should be answered from property FAQ";
                    $optimal[] = 'PortalLeadParser must detect the template and ResolveListingTool must match the property';
                } elseif ($outcome === 'delivered') {
                    $optimal[] = 'Bot correctly resolved the portal lead and answered from property data';
                }
                break;

            case 'buyer_inquiry':
                if ($outcome === 'handoff') {
                    if ($reason === 'citation_violation') {
                        $rating    = 'FAIL';
                        $issues[]  = 'Bot handed off buyer inquiry due to citation_violation — bare price/area number in reply';
                        $optimal[] = 'NumberProvenance should allow customer-supplied numbers; ReplyRedactor should repair before handoff';
                    } elseif ($reason === 'customer_request') {
                        $rating    = 'WARN';
                        $issues[]  = 'Bot escalated a buyer inquiry (reason=customer_request) — verify customer actually asked for a human';
                        $optimal[] = 'Review trigger; if asking about a property, bot should search first';
                    } else {
                        $rating    = 'WARN';
                        $issues[]  = "Bot handed off buyer inquiry (reason={$reason})";
                        $optimal[] = 'For specific queries (location/budget/type), bot should search before handing off';
                    }
                } elseif ($outcome === 'delivered') {
                    $hasPlaceholder = (bool) preg_match('/\{\{p:\d+\|/', $reply);
                    $sayNoResults   = str_contains($reply, 'ما لقيت') || str_contains($reply, 'ما حصلت') || str_contains($reply, 'ما أقدر أساعدك في عرض');
                    if ($hasPlaceholder || $sayNoResults) {
                        $optimal[] = $hasPlaceholder
                            ? 'Bot found properties and replied with correct citation placeholders'
                            : 'Bot searched and gracefully reported no results';
                    } else {
                        $rating    = 'WARN';
                        $issues[]  = 'Bot answered buyer inquiry without placeholders and without saying no results found';
                        $optimal[] = 'Verify SearchInventoryTool was called; citations must be used if results returned';
                    }
                } elseif ($outcome === 'failed') {
                    $rating    = 'FAIL';
                    $issues[]  = "Bot failed on a buyer inquiry (reason={$reason})";
                    $optimal[] = 'Investigate agent loop; check for budget_exhausted or token accounting issues';
                }
                break;

            default: // 'contact_request', 'oot', 'unknown'
                if ($outcome === 'delivered') {
                    $optimal[] = 'Bot handled an unclear or out-of-scope message gracefully';
                } elseif ($outcome === 'handoff') {
                    $rating    = 'WARN';
                    $issues[]  = "Bot handed off an unclear/out-of-scope message (reason={$reason})";
                    $optimal[] = 'For ambiguous messages, ask a clarifying question before handing off';
                } else {
                    $rating    = 'WARN';
                    $issues[]  = "Unexpected outcome ({$outcome}) for an unclear message";
                    $optimal[] = 'Investigate pipeline';
                }
        }

        if (!empty($turnIssues) && $rating === 'PASS') {
            $rating = 'WARN';
        }
        if (empty($optimal)) {
            $optimal[] = 'No changes needed';
        }

        return compact('rating', 'issues', 'optimal', 'category');
    }

    private function detectCategory(string $trigger, array $turns): string
    {
        $text = mb_strtolower(trim($trigger));

        // Portal lead template (aqar.fm / wasalt / bayut / property finder)
        if (preg_match('/aqar\.fm|عقار\.fm|wasalt\.com|bayut\.com|propertyfinder\.ae|أرغب في التواصل مع المعلن/u', $trigger)) {
            return 'portal_lead';
        }

        // Seller
        $sellerTerms = ['للبيع', 'للإيجار بالسنوي', 'أنا وكيله', 'انا وكيله', 'يطلب', 'طالب', 'بناء شخصي'];
        foreach ($sellerTerms as $t) {
            if (str_contains($text, mb_strtolower($t))) {
                return 'seller';
            }
        }

        // Greeting
        if ($this->isGreeting($trigger)) {
            return 'greeting';
        }

        // Contact request
        if (preg_match('/اتصل|رقمك|تواصل معي|ابعث رقمك/u', $text)) {
            return 'contact_request';
        }

        if (mb_strlen($trigger) < 10) {
            return 'oot';
        }

        // Buyer inquiry
        $buyerTerms = ['أبحث', 'ابحث', 'أبغى', 'ابغى', 'أريد', 'اريد', 'شقة', 'فيلا', 'ارض', 'أرض', 'عمارة', 'ميزانية', 'غرف', 'سعر', 'إيجار', 'ايجار', 'شراء', 'هل يوجد', 'متوفر'];
        foreach ($buyerTerms as $t) {
            if (str_contains($text, mb_strtolower($t))) {
                return 'buyer_inquiry';
            }
        }

        return 'unknown';
    }

    private function isGreeting(string $text): bool
    {
        $patterns = ['السلام عليكم', 'مرحبا', 'مرحباً', 'هلا', 'صباح الخير', 'مساء الخير', 'أهلاً', 'اهلاً', 'اهلا', 'أهلا'];
        $text = mb_strtolower(trim($text));
        if (mb_strlen($text) < 40) {
            foreach ($patterns as $p) {
                if (str_contains($text, $p)) {
                    return true;
                }
            }
        }
        return false;
    }

    /** @return list<string> */
    private function findWorstTurnIssues(array $turns): array
    {
        $issues = [];
        foreach ($turns as $t) {
            if ($t['skipped'] ?? false) {
                continue;
            }
            $outcome = $t['outcome'] ?? '';
            $reason  = $t['reason'] ?? '';
            if ($outcome === 'handoff' && $reason === 'citation_violation') {
                $issues[] = "citation_violation on turn {$t['turn']}: CitationGuard fired on bare numbers";
            }
            if ($outcome === 'failed' && $reason === 'budget_exhausted') {
                $issues[] = "budget_exhausted on turn {$t['turn']}: agent loop hit token/step limit";
            }
            if ($outcome === 'php_error') {
                $issues[] = 'PHP exception on turn ' . $t['turn'] . ': ' . mb_substr((string) ($t['reason'] ?? ''), 0, 100);
            }
        }
        return $issues;
    }
}
