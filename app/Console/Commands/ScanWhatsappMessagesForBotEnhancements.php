<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communication\WhatsApp\Bot\MessageFactExtractor;
use App\Domain\Communication\WhatsApp\Bot\SlotFillingPolicy;
use App\Models\WaNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Modules\WhatsappAI\Entities\WhatsappMessage;

/**
 * Scan real rows from Modules/WhatsappAI whatsapp_messages to surface the most common
 * deterministic pipeline gaps (fact extraction + slot-fill policy).
 *
 * This command is intentionally LLM-free: it flags issues we can fix with code + tests.
 */
final class ScanWhatsappMessagesForBotEnhancements extends Command
{
    protected $signature = 'ai:scan-whatsapp-messages
                            {--wa_number_id= : WaNumber id (defaults to first active)}
                            {--tenant= : Tenant user_id (0 = all tenants; defaults to WaNumber.user_id)}
                            {--limit-conversations=200 : Max conversations to scan}
                            {--limit-messages=30 : Max inbound messages to scan per conversation}
                            {--max-examples=30 : Max examples stored per issue}
                            {--output=storage/app/ai/whatsapp-messages-scan.json : Output JSON path}';

    protected $description = 'Scan whatsapp_messages for bot improvement opportunities and output examples + counts.';

    public function handle(SlotFillingPolicy $slotPolicy): int
    {
        $waNumberId = (int) ($this->option('wa_number_id') ?: 0);
        if ($waNumberId <= 0) {
            $waNumberId = (int) (WaNumber::where('status', 'active')->orderBy('id')->value('id') ?: 0);
        }

        if ($waNumberId <= 0) {
            $this->error('No active WaNumber found.');
            return self::FAILURE;
        }

        $waNumber = WaNumber::find($waNumberId);
        if ($waNumber === null) {
            $this->error("WaNumber {$waNumberId} not found.");
            return self::FAILURE;
        }

        $tenantOpt = $this->option('tenant');
        $tenantId = $tenantOpt === null ? (int) $waNumber->user_id : (int) $tenantOpt;

        $limitConvs  = max(1, min(5_000, (int) ($this->option('limit-conversations') ?? 200)));
        $limitMsgs   = max(1, min(200, (int) ($this->option('limit-messages') ?? 30)));
        $maxExamples = max(5, min(200, (int) ($this->option('max-examples') ?? 30)));
        $output      = (string) ($this->option('output') ?? 'storage/app/ai/whatsapp-messages-scan.json');

        $convQuery = WhatsappConversation::query()
            ->orderByDesc('last_message_at')
            ->limit($limitConvs);

        if ($tenantId > 0) {
            $convQuery->where('user_id', $tenantId);
        }

        $conversations = $convQuery->get(['id', 'user_id', 'customer_phone', 'message_count', 'last_message_at']);

        $counters = [
            'conversations_scanned' => 0,
            'messages_scanned'      => 0,
            'issues' => [
                'street_contains_budget_words'             => 0,
                'slot_policy_asks_bedrooms_for_skip_type'  => 0,
                'budget_keyword_present_but_not_detected'  => 0,
                'type_keyword_present_but_not_detected'    => 0,
                'arabic_indic_digits_present_but_budget_not_detected' => 0,
            ],
        ];

        $examples = [
            'street_contains_budget_words' => [],
            'slot_policy_asks_bedrooms_for_skip_type' => [],
            'budget_keyword_present_but_not_detected' => [],
            'type_keyword_present_but_not_detected' => [],
            'arabic_indic_digits_present_but_budget_not_detected' => [],
        ];

        foreach ($conversations as $conv) {
            $counters['conversations_scanned']++;

            /** @var \Illuminate\Support\Collection<int, WhatsappMessage> $messages */
            $messages = WhatsappMessage::query()
                ->where('conversation_id', $conv->id)
                ->where(function ($q) {
                    $q->whereNull('direction')->orWhere('direction', 'inbound');
                })
                ->where('message_type', 'text')
                ->orderByDesc('id')
                ->limit($limitMsgs)
                ->get(['id', 'content', 'created_at'])
                ->reverse()
                ->values();

            foreach ($messages as $msg) {
                $counters['messages_scanned']++;

                $text = trim((string) ($msg->content ?? ''));
                if ($text === '') {
                    continue;
                }

                $facts = MessageFactExtractor::extract([$text]);

                // 1) Street/district contains budget words (should not happen)
                $district = (string) ($facts['district'] ?? '');
                if ($district !== '' && preg_match('/(?:بميزانية|ميزانية|بسعر|بحدود|لميزانية)/u', $district)) {
                    $counters['issues']['street_contains_budget_words']++;
                    $this->pushExample($examples['street_contains_budget_words'], $maxExamples, $conv->id, (int) $msg->id, $text, $facts);
                }

                // 2) Slot policy asks bedrooms even though type should skip bedrooms
                $budgetKnown   = isset($facts['budget_max']) || isset($facts['budget_min']);
                $locationKnown = isset($facts['city']) || isset($facts['district']);
                $type          = (string) ($facts['type'] ?? '');
                $skipBedroomsTypes = [
                    'office', 'land', 'warehouse', 'building',
                    'مكتب', 'أرض', 'ارض', 'مستودع',
                    'عمارة', 'عمارة سكنية', 'عمارة تجارية', 'مبنى',
                    'محل', 'محل تجاري',
                ];

                if ($budgetKnown && $locationKnown && $type !== '' && in_array($type, $skipBedroomsTypes, true)) {
                    $q = $slotPolicy->nextQuestion($facts, 'property_search');
                    if (is_string($q) && Str::contains($q, ['غرف', 'غرفة'])) {
                        $counters['issues']['slot_policy_asks_bedrooms_for_skip_type']++;
                        $this->pushExample(
                            $examples['slot_policy_asks_bedrooms_for_skip_type'],
                            $maxExamples,
                            $conv->id,
                            (int) $msg->id,
                            $text,
                            array_merge($facts, ['next_question' => $q])
                        );
                    }
                }

                // 3) Budget keywords present but extractor missed a budget value
                if (! isset($facts['budget_max']) && ! isset($facts['budget_min'])
                    && preg_match('/(?:ميزاني(?:تي)?|ميزانية|بميزانية|بحدود|بسعر|سعره|سعرها)/u', $text)) {
                    $counters['issues']['budget_keyword_present_but_not_detected']++;
                    $this->pushExample($examples['budget_keyword_present_but_not_detected'], $maxExamples, $conv->id, (int) $msg->id, $text, $facts);
                }

                // 3b) Arabic-Indic digits present but budget not detected (likely missing digit normalization)
                if (! isset($facts['budget_max']) && ! isset($facts['budget_min'])
                    && preg_match('/[\x{0660}-\x{0669}]/u', $text)
                    && preg_match('/(?:مليون|ألف|الف|ريال)/u', $text)) {
                    $counters['issues']['arabic_indic_digits_present_but_budget_not_detected']++;
                    $this->pushExample(
                        $examples['arabic_indic_digits_present_but_budget_not_detected'],
                        $maxExamples,
                        $conv->id,
                        (int) $msg->id,
                        $text,
                        $facts
                    );
                }

                // 4) Type-ish word present but extractor missed type
                if (! isset($facts['type'])
                    && preg_match('/(?:شقة|شقه|فيلا|فله|فلة|ارض|أرض|عمارة|مكتب|محل|مستودع|دوبلكس|استراحة|قصر|مزرعة)/u', $text)) {
                    $counters['issues']['type_keyword_present_but_not_detected']++;
                    $this->pushExample($examples['type_keyword_present_but_not_detected'], $maxExamples, $conv->id, (int) $msg->id, $text, $facts);
                }
            }
        }

        $payload = [
            'tenant_id'    => $tenantId,
            'wa_number_id' => $waNumberId,
            'scanned_at'   => now()->toIso8601String(),
            'counters'     => $counters,
            'examples'     => $examples,
        ];

        $outputPath = base_path($output);
        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('Scan complete.');
        $this->line('Output: ' . $outputPath);
        $this->line('Messages scanned: ' . $counters['messages_scanned']);

        $this->table(
            ['Issue', 'Count'],
            [
                ['street_contains_budget_words', (string) $counters['issues']['street_contains_budget_words']],
                ['slot_policy_asks_bedrooms_for_skip_type', (string) $counters['issues']['slot_policy_asks_bedrooms_for_skip_type']],
                ['budget_keyword_present_but_not_detected', (string) $counters['issues']['budget_keyword_present_but_not_detected']],
                ['arabic_indic_digits_present_but_budget_not_detected', (string) $counters['issues']['arabic_indic_digits_present_but_budget_not_detected']],
                ['type_keyword_present_but_not_detected', (string) $counters['issues']['type_keyword_present_but_not_detected']],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $bucket
     * @param  array<string, mixed>  $facts
     */
    private function pushExample(array &$bucket, int $maxExamples, int $conversationId, int $messageId, string $text, array $facts): void
    {
        if (count($bucket) >= $maxExamples) {
            return;
        }

        $bucket[] = [
            'conversation_id' => $conversationId,
            'message_id'      => $messageId,
            'text'            => mb_substr($text, 0, 220),
            'facts'           => $facts,
        ];
    }
}

