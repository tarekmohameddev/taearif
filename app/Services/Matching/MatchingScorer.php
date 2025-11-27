<?php

namespace App\Services\Matching;

use App\Support\DTO\UnifiedRequest;
use Psr\Log\LoggerInterface;
use OpenAI; // openai-php/client

class MatchingScorer
{
    public function __construct(
        private PromptBuilder $prompts,
        private LoggerInterface $logger
    ) {}

    /**
     * Calls OpenAI and returns array: [ property_id => ['ai_score' => int, 'matched_criteria' => [], 'explanation' => ''] ]
     */
    public function scoreWithAI(UnifiedRequest $req, array $properties): array
    {
        $lang = $req->lang === 'ar' ? 'ar' : 'en';
        $payload = $this->prompts->buildScoringPrompt($req, $properties, $lang);

        try {
            $client = OpenAI::client(config('openai.api_key'));
            $response = $client->chat()->create([
                'model' => config('openai.model', 'gpt-4-turbo'),
                'temperature' => (float) config('openai.temperature', 0.3),
                'max_tokens' => (int) config('openai.max_tokens', 2000),
                'messages' => [
                    ['role' => 'system', 'content' => $payload['system']],
                    ['role' => 'user', 'content' => $payload['user']],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response->choices[0]->message->content ?? '';
            $this->logger->info('MatchingScorer: OpenAI raw response', [
                'content' => $content,
            ]);
            
            $data = json_decode($content, true);
            
            // Accept both 'results' and 'properties' keys (OpenAI may use either)
            $resultsArray = null;
            if (isset($data['results']) && is_array($data['results'])) {
                $resultsArray = $data['results'];
            } elseif (isset($data['properties']) && is_array($data['properties'])) {
                $resultsArray = $data['properties'];
            }
            
            if (!is_array($data) || $resultsArray === null) {
                throw new \RuntimeException('Invalid AI response schema');
            }

            $map = [];
            foreach ($resultsArray as $row) {
                $pid = $row['property_id'] ?? $row['id'] ?? null;
                if (!$pid) continue;
                $map[(int) $pid] = [
                    'ai_score' => (int) ($row['ai_score'] ?? $row['score'] ?? 0),
                    'matched_criteria' => $row['matched_criteria'] ?? [],
                    'explanation' => $row['explanation'] ?? null,
                ];
            }
            return $map;
        } catch (\Throwable $e) {
            $this->logger->error('AI scoring failed', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 2000),
            ]);
            return [];
        }
    }
}


