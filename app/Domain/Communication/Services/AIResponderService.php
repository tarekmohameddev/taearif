<?php

namespace App\Domain\Communication\Services;

use App\Models\Message;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use OpenAI;

class AIResponderService
{
    public function suggestReply(Message $message, array $context = []): ?string
    {
        $content = trim((string) $message->content);
        $channel = strtolower((string) ($message->conversation->channel ?? ''));
        if ($content === '' || $channel !== 'whatsapp') {
            return null;
        }
        if (! config('communication.enabled', false) || ! config('communication.ai.enabled', false)) {
            return null;
        }

        $apiKey = env('OPENAI_API_KEY');
        if ($apiKey === null || $apiKey === '') {
            Log::warning('AIResponderService: OPENAI_API_KEY missing, skipping suggestion');
            return null;
        }

        $timeout = (int) config('communication.ai.timeout_seconds', 15);
        $maxChars = (int) config('communication.automation.suggest_max_chars', 300);

        try {
            $httpClient = new GuzzleClient(['timeout' => $timeout]);
            $client = OpenAI::factory()
                ->withApiKey($apiKey)
                ->withHttpClient($httpClient)
                ->make();

            $prompt = 'Suggest a brief, professional reply to this message. One or two short sentences only. Do not include URLs unless the message explicitly asks for a link.';
            $response = $client->chat()->create([
                'model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt . "\n\nMessage: " . $content],
                ],
                'max_tokens' => 150,
            ]);

            $text = $response->choices[0]->message->content ?? '';
            $text = trim(strip_tags($text));
            if ($text === '') {
                return null;
            }
            if (mb_strlen($text) > $maxChars) {
                $text = mb_substr($text, 0, $maxChars);
            }
            if ($this->looksLikeBlockedUrl($text) && ! $this->inboundAsksForLink($content)) {
                return null;
            }
            return $text;
        } catch (\Throwable $e) {
            Log::warning('AIResponderService: provider error', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function looksLikeBlockedUrl(string $text): bool
    {
        return (bool) preg_match('#https?://\S+#i', $text);
    }

    private function inboundAsksForLink(string $content): bool
    {
        $lower = mb_strtolower($content);
        $hints = ['رابط', 'link', 'url', 'الرابط', 'أرسل الرابط', 'أرسل رابط', 'أعطني الرابط', 'أعطني رابط'];
        foreach ($hints as $hint) {
            if (str_contains($lower, $hint)) {
                return true;
            }
        }
        return false;
    }
}
