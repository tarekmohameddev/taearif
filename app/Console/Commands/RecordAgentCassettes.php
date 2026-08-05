<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Agent\Runtime\AgentLoop;
use App\Domain\Ai\Agent\Runtime\StepBudget;
use App\Domain\Ai\Agent\Runtime\ToolRegistry;
use App\Domain\Ai\Agent\DTOs\AgentMessage;
use App\Domain\Ai\Agent\Transport\OpenAiTransport;
use App\Domain\Ai\Agent\Transport\RecordingTransport;
use App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool;
use App\Domain\RealEstateAgent\Brain\Employee;
use App\Domain\RealEstateAgent\Eval\ReplayRunner;
use App\Domain\RealEstateAgent\Tools\EscalateToHumanTool;
use App\Domain\RealEstateAgent\Tools\GetPropertyDetailsTool;
use App\Domain\RealEstateAgent\Tools\ProposeViewingTool;
use App\Domain\RealEstateAgent\Tools\RecordCustomerFactTool;
use App\Domain\RealEstateAgent\Tools\SearchInventoryTool;
use App\Domain\RealEstateAgent\Tools\SearchKnowledgeTool;
use App\Domain\Ai\Knowledge\EmbeddingService;
use App\Domain\Ai\Knowledge\RetrievalService;
use Illuminate\Console\Command;

/**
 * Record cassette files for the deterministic agent test suite.
 *
 * Run once against live LLMs to capture request-response pairs, then commit the
 * cassettes.  Tests use ReplayTransport instead of the real API.
 *
 *   php artisan ai:agent:record --corpus=tests/Fixtures/agent/corpus
 */
final class RecordAgentCassettes extends Command
{
    protected $signature = 'ai:agent:record
        {--corpus=tests/Fixtures/agent/corpus : Path to corpus fixture directory}
        {--cassettes=tests/Fixtures/agent/cassettes : Path to cassette output directory}
        {--tenant=1 : Tenant user_id to use for tool execution}
        {--model= : Model override (defaults to config openai.chat_model)}';

    protected $description = 'Record LLM cassettes for deterministic agent test suite replay.';

    public function __construct(
        private readonly PropertySearchTool $propertySearchTool,
        private readonly EmbeddingService   $embeddingService,
        private readonly RetrievalService   $retrievalService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $corpusDir   = (string) $this->option('corpus');
        $cassetteDir = (string) $this->option('cassettes');
        $tenantId    = (int)    $this->option('tenant');
        $model       = (string) ($this->option('model') ?: config('openai.chat_model', 'gpt-5-mini'));

        $fixtures = $this->loadFixtures($corpusDir);
        if (empty($fixtures)) {
            $this->warn("No fixtures found in {$corpusDir}");
            return Command::FAILURE;
        }

        $this->info(sprintf('Recording %d fixtures → %s (model: %s)', count($fixtures), $cassetteDir, $model));

        $apiKey  = (string) config('openai.api_key', '');
        $baseUrl = 'https://api.openai.com/v1';

        $toolRegistry = $this->buildToolRegistry();
        $runner       = new ReplayRunner();

        $recorded = 0;
        foreach ($fixtures as $fixture) {
            $id          = (string) ($fixture['id'] ?? 'unknown');
            $cassetteId  = 'corpus_' . $id;

            $inner      = new OpenAiTransport($apiKey, $baseUrl, 'openai');
            $transport  = new RecordingTransport($inner, $cassetteDir, $cassetteId);

            $this->buildSystemMessage(); // warmup

            try {
                $result = $runner->run($fixture, $transport, $toolRegistry, $tenantId, $model);
                $status = $result->passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("[{$status}] {$id}" . ($result->passed ? '' : ': ' . implode(', ', $result->failures)));
                $recorded++;
            } catch (\Throwable $e) {
                $this->error("[ERROR] {$id}: " . $e->getMessage());
            }
        }

        $this->info("Recorded {$recorded} cassettes → {$cassetteDir}");
        return Command::SUCCESS;
    }

    private function loadFixtures(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $files    = glob($dir . '/**/*.json') ?: [];
        $files    = array_merge($files, glob($dir . '/*.json') ?: []);
        $fixtures = [];
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file) ?: '', true);
            if (is_array($data)) {
                $fixtures[] = $data;
            }
        }
        return $fixtures;
    }

    private function buildToolRegistry(): ToolRegistry
    {
        return new ToolRegistry([
            new SearchInventoryTool($this->propertySearchTool),
            new GetPropertyDetailsTool(),
            new SearchKnowledgeTool($this->embeddingService, $this->retrievalService),
            new ProposeViewingTool(),
            new EscalateToHumanTool(),
            new RecordCustomerFactTool(),
        ]);
    }

    private function buildSystemMessage(): AgentMessage
    {
        return AgentMessage::system(
            'أنت مساعد عقاري. أجب على أسئلة العميل باستخدام الأدوات المتاحة. ' .
            'استخدم صيغة {{p:ID|field}} للإشارة لأرقام العقارات.'
        );
    }
}
