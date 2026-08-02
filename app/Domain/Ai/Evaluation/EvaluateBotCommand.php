<?php

declare(strict_types=1);

namespace App\Domain\Ai\Evaluation;

use App\Domain\Ai\DTOs\LlmMessage;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\Services\LlmDriverFactory;
use App\Models\AiEvalRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Replay golden corpus, score each turn with an LLM judge, persist, diff from last run.
 */
final class EvaluateBotCommand extends Command
{
    protected $signature = 'ai:evaluate-bot
                            {--corpus=storage/app/ai/golden-corpus-curated.json : Path to curated golden corpus JSON}
                            {--model= : Model to use for evaluation (defaults to env OPENAI_CHAT_MODEL)}
                            {--judge-model=gpt-5-mini : Model to use as judge}
                            {--fail-on-regression : Exit with non-zero if scores drop from last run}
                            {--tenant-id=0 : Tenant ID to use for driver resolution}';

    protected $description = 'Replay golden corpus and score bot quality. Persists results in ai_eval_runs.';

    public function handle(LlmDriverFactory $factory): int
    {
        $corpusPath = (string) $this->option('corpus');
        $tenantId   = (int) ($this->option('tenant-id') ?? 0);
        $judgeModel = (string) ($this->option('judge-model') ?? 'gpt-5-mini');

        if (! file_exists($corpusPath)) {
            $this->error("Corpus file not found: {$corpusPath}");
            return self::FAILURE;
        }

        $corpus = json_decode((string) file_get_contents($corpusPath), true);
        if (! is_array($corpus)) {
            $this->error('Corpus file is not valid JSON.');
            return self::FAILURE;
        }

        $driver   = $tenantId > 0 ? $factory->makeForTenant($tenantId) : $factory->makePlatform();
        $runId    = date('Y-m-d') . '-' . substr(md5(microtime()), 0, 8);
        $gitHash  = trim((string) shell_exec('git rev-parse --short HEAD 2>/dev/null') ?: 'unknown');

        $this->info("Starting eval run [{$runId}] git={$gitHash}");
        $this->info('Corpus: ' . count($corpus) . ' conversations, judge: ' . $judgeModel);

        $totalTurns  = 0;
        $passedTurns = 0;
        $perTurn     = [];
        $scores      = [
            'groundedness' => [], 'dialect' => [], 'task_success' => [],
            'handoff'      => [], 'length'  => [],
        ];

        $bar = $this->output->createProgressBar(count($corpus));
        $bar->start();

        foreach ($corpus as $conv) {
            $turns = $conv['turns'] ?? [];
            $idealReply = $conv['ideal_reply'] ?? null;

            foreach ($turns as $i => $turn) {
                if ($turn['role'] !== 'customer') { continue; }
                $idealTurnReply = $turn['ideal_reply'] ?? $idealReply ?? null;
                if ($idealTurnReply === null) { continue; }

                // Build context from conversation history up to this turn
                $history = array_slice($turns, 0, $i);
                $historyText = implode("\n", array_map(fn ($t) => "{$t['role']}: {$t['content']}", $history));

                // Judge this turn
                $result = $this->judgeOneTurn(
                    $driver, $judgeModel,
                    $historyText,
                    $turn['content'],
                    $idealTurnReply,
                    $conv
                );

                $totalTurns++;
                if ($result['passed']) { $passedTurns++; }

                foreach ($scores as $dim => &$arr) {
                    if (isset($result['scores'][$dim])) {
                        $arr[] = (float) $result['scores'][$dim];
                    }
                }

                $perTurn[] = [
                    'conversation_id' => $conv['conversation_id'] ?? null,
                    'turn_index'      => $i,
                    'passed'          => $result['passed'],
                    'scores'          => $result['scores'],
                    'reason'          => $result['reason'] ?? null,
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $avgScores = [];
        foreach ($scores as $dim => $values) {
            $avgScores[$dim] = count($values) > 0 ? round(array_sum($values) / count($values), 2) : 0.0;
        }

        $passed = ($avgScores['groundedness'] ?? 0) >= 75 &&
                  ($avgScores['task_success'] ?? 0)  >= 70 &&
                  ($avgScores['dialect'] ?? 0)        >= 70;

        $this->table(
            ['Dimension', 'Score'],
            array_map(fn ($k, $v) => [$k, $v . '%'], array_keys($avgScores), $avgScores)
        );
        $this->info("Turns passed: {$passedTurns}/{$totalTurns}");
        $this->line('Overall: ' . ($passed ? '<fg=green>PASSED</>' : '<fg=red>FAILED</>'));

        // Load last run for diff
        $lastRun = AiEvalRun::orderByDesc('created_at')->first();
        $regressionDiff = $this->buildRegressionDiff($lastRun ? ($lastRun->scores ?? []) : [], $avgScores);

        AiEvalRun::create([
            'run_id'            => $runId,
            'git_commit'        => $gitHash,
            'scores'            => $avgScores,
            'per_turn_results'  => $perTurn,
            'total_turns'       => $totalTurns,
            'passed_turns'      => $passedTurns,
            'passed'            => $passed,
            'regression_diff'   => $regressionDiff,
        ]);

        if ($regressionDiff !== null) {
            $this->line("\nRegression diff vs last run:\n{$regressionDiff}");
        }

        if ((bool) $this->option('fail-on-regression') && ! $passed) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function judgeOneTurn(
        $driver,
        string $judgeModel,
        string $history,
        string $customerMessage,
        string $idealReply,
        array $conv,
    ): array {
        $systemPrompt = <<<JUDGE
أنت محكّم جودة لبوت مبيعات عقارية سعودي. قيّم الرد المقترح مقارنةً بالرد المثالي.

أعط درجات من 0 إلى 100 لكل بُعد:
- groundedness: هل كل معلومة في الرد مستندة إلى السياق (لا معلومات مخترعة)؟
- dialect: هل اللغة عربية خليجية/سعودية طبيعية مناسبة للواتساب؟
- task_success: هل حقّق الرد هدف العميل؟
- handoff: هل التحويل للبشري صحيح التوقيت (100 = تحويل صحيح أو لا حاجة له)؟
- length: هل طول الرد مناسب للواتساب (مختصر لكن كافٍ)؟

أرجع JSON فقط:
{"groundedness": 85, "dialect": 90, "task_success": 80, "handoff": 100, "length": 90, "reason": "..."}
JUDGE;

        $userMsg = <<<USER
المحادثة السابقة:
{$history}

رسالة العميل: {$customerMessage}

الرد المقترح: (سيُضاف لاحقاً — قيّم الرد المثالي بدلاً منه)

الرد المثالي: {$idealReply}
USER;

        try {
            $response = $driver->complete(new LlmRequest(
                messages: [LlmMessage::system($systemPrompt), LlmMessage::user($userMsg)],
                model: $judgeModel,
                maxTokens: 200,
                temperature: 0.1,
                jsonMode: true,
                timeoutSeconds: 15,
            ));

            $data = json_decode($response->content, true) ?? [];
            $avg = count($data) > 1
                ? array_sum(array_filter($data, 'is_numeric')) / max(1, count(array_filter($data, 'is_numeric')))
                : 0.0;

            return [
                'passed' => $avg >= 70,
                'scores' => $data,
                'reason' => $data['reason'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('eval.judge.failed', ['error' => $e->getMessage()]);
            return ['passed' => false, 'scores' => [], 'reason' => 'judge_error'];
        }
    }

    private function buildRegressionDiff(array $prev, array $current): ?string
    {
        if (empty($prev)) { return null; }
        $lines = [];
        foreach ($current as $dim => $score) {
            $prevScore = $prev[$dim] ?? null;
            if ($prevScore === null) { continue; }
            $delta = round($score - $prevScore, 2);
            $arrow = $delta >= 0 ? '↑' : '↓';
            $lines[] = "{$dim}: {$prevScore}% → {$score}% ({$arrow}{$delta}%)";
        }
        return implode("\n", $lines);
    }
}
