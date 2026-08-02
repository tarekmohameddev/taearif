<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot\Jobs;

use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\DTOs\LlmMessage;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\Services\LlmDriverFactory;
use App\Domain\Ai\Services\UsageRecorder;
use App\Models\AiCustomerProfile;
use App\Models\Message;
use App\Models\WaConversationAiState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SummarizeConversationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        private readonly int $conversationId,
        private readonly int $tenantId,
        private readonly string $customerPhone,
    ) {
        $this->onQueue('ai');
    }

    public function handle(LlmDriverFactory $factory, UsageRecorder $usage): void
    {
        $state = WaConversationAiState::where('conversation_id', $this->conversationId)->first();
        if ($state === null) { return; }

        // Load messages since last summary
        $messages = Message::where('conversation_id', $this->conversationId)
            ->where('user_id', $this->tenantId)
            ->when($state->summary_through_message_id, fn ($q) => $q->where('id', '>', $state->summary_through_message_id))
            ->orderBy('id')
            ->limit(60)
            ->get();

        if ($messages->count() < 6) {
            // Not enough new turns to summarize yet
            return;
        }

        $lastMessageId = $messages->last()?->id;
        $transcript = $messages->map(function (Message $m) {
            $role = $m->direction === 'inbound' ? 'عميل' : 'موظف';
            return $role . ': ' . ($m->content ?? '');
        })->implode("\n");

        // Build existing summary context
        $existingSummary = $this->buildExistingSummaryText($state);

        $prompt = <<<PROMPT
أنت محلل محادثات عقارية. لديك محادثة واتساب بين عميل وموظف عقاري.
استخرج وحدّث الملخص الهيكلي التالي:

الملخص الحالي:
{$existingSummary}

المحادثة الجديدة:
{$transcript}

أرجع JSON فقط بهذا الشكل (بدون أي نص خارجه):
{
  "situation":    "وصف مختصر لوضع العميل",
  "requirements": "ما يطلبه العميل بوضوح",
  "commitments":  "ما وعد به الموظف",
  "objections":   "ما رفضه أو تحفظ عليه العميل",
  "tone":         "friendly|frustrated|urgent|suspicious",
  "facts": {
    "intent":    "sale|rent|inquiry",
    "type":      "apartment|villa|office|land|other",
    "city":      "اسم المدينة",
    "district":  "اسم الحي",
    "budget_max": 850000,
    "bedrooms":  3,
    "name":      "اسم العميل",
    "urgency":   "low|medium|high"
  }
}
PROMPT;

        try {
            $driver = $factory->makeForTenant($this->tenantId);
            $response = $driver->complete(new LlmRequest(
                messages: [LlmMessage::user($prompt)],
                model: env('OPENAI_CHAT_MODEL', 'gpt-5-mini'),
                maxTokens: 500,
                temperature: 0.1,
                jsonMode: true,
                timeoutSeconds: 30,
            ));

            $usage->record($this->tenantId, 'summarize', $response, $this->conversationId);

            $data = json_decode($response->content, true);
            if (! is_array($data)) {
                Log::warning('bot.summarize.invalid_json', ['conversation_id' => $this->conversationId]);
                return;
            }

            $state->update([
                'summary_through_message_id' => $lastMessageId,
                'situation'    => $data['situation'] ?? $state->situation,
                'requirements' => $data['requirements'] ?? $state->requirements,
                'commitments'  => $data['commitments'] ?? $state->commitments,
                'objections'   => $data['objections'] ?? $state->objections,
                'tone'         => $data['tone'] ?? $state->tone,
                'facts'        => array_merge($state->facts ?? [], $data['facts'] ?? []),
            ]);

            // Update customer profile with durable facts
            $this->updateCustomerProfile($data['facts'] ?? []);
        } catch (\Throwable $e) {
            Log::error('bot.summarize.failed', [
                'conversation_id' => $this->conversationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildExistingSummaryText(WaConversationAiState $state): string
    {
        $parts = [];
        if ($state->situation)   { $parts[] = "الوضع: {$state->situation}"; }
        if ($state->requirements){ $parts[] = "المتطلبات: {$state->requirements}"; }
        if ($state->commitments) { $parts[] = "الالتزامات: {$state->commitments}"; }
        if ($state->objections)  { $parts[] = "الاعتراضات: {$state->objections}"; }
        $facts = $state->facts ?? [];
        if (! empty($facts)) {
            $parts[] = "حقائق: " . json_encode($facts, JSON_UNESCAPED_UNICODE);
        }
        return $parts ? implode("\n", $parts) : 'لا يوجد ملخص سابق.';
    }

    private function updateCustomerProfile(array $facts): void
    {
        if (empty($facts)) { return; }
        $durableKeys = ['city', 'district', 'type', 'bedrooms', 'budget_max', 'name', 'intent'];
        $durable = array_intersect_key($facts, array_flip($durableKeys));
        $durable = array_filter($durable, fn ($v) => $v !== null && $v !== '');
        if (empty($durable)) { return; }

        $profile = AiCustomerProfile::firstOrCreate(
            ['user_id' => $this->tenantId, 'phone' => $this->customerPhone],
            ['first_contact_at' => now()]
        );
        $existing = $profile->durable_facts ?? [];
        $profile->update([
            'durable_facts'   => array_merge($existing, $durable),
            'last_contact_at' => now(),
            'name'            => $durable['name'] ?? $profile->name,
        ]);
    }
}
