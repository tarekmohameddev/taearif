<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Brain;

use App\Models\WaAiConfig;

/**
 * Holds the per-tenant agent configuration loaded from WaAiConfig.
 *
 * The `playbook` JSON column on wa_ai_configs stores tenant-specific
 * overrides for tone, few-shot examples, forbidden topics, etc.
 */
final class Playbook
{
    public readonly string $assistantName;
    public readonly string $goal;         // salesman | support | booking
    public readonly string $tone;         // friendly | formal | enthusiastic
    public readonly string $language;     // ar
    public readonly int    $replyLengthTarget;
    public readonly bool   $discloseAsAssistant;
    public readonly ?string $customInstructions;
    public readonly ?array  $businessHours;
    public readonly ?string $timezone;

    /** @var array<int, array{customer: string, bot: string}> */
    public readonly array $fewShotExamples;

    private function __construct(array $data)
    {
        $this->assistantName       = (string) ($data['assistant_name']       ?? 'المساعد');
        $this->goal                = (string) ($data['goal']                 ?? 'support');
        $this->tone                = (string) ($data['tone']                 ?? 'friendly');
        $this->language            = (string) ($data['language']             ?? 'ar');
        $this->replyLengthTarget   = (int)    ($data['reply_length_target']  ?? 200);
        $this->discloseAsAssistant = (bool)   ($data['disclose_as_assistant'] ?? true);
        $this->customInstructions  = isset($data['custom_instructions']) ? (string) $data['custom_instructions'] : null;
        $this->businessHours       = isset($data['business_hours']) ? (array) $data['business_hours'] : null;
        $this->timezone            = isset($data['timezone']) ? (string) $data['timezone'] : null;
        $this->fewShotExamples     = (array) ($data['few_shot_examples'] ?? []);
    }

    public static function fromConfig(WaAiConfig $config): self
    {
        $base = [
            'assistant_name'        => $config->assistant_name,
            'goal'                  => $config->goal,
            'tone'                  => $config->tone,
            'language'              => $config->language,
            'reply_length_target'   => $config->reply_length_target,
            'disclose_as_assistant' => $config->disclose_as_assistant,
            'custom_instructions'   => $config->custom_instructions,
            'business_hours'        => $config->business_hours,
            'timezone'              => $config->timezone,
        ];

        // Merge playbook overrides from the JSON column
        $overrides = is_array($config->playbook) ? $config->playbook : [];
        return new self(array_merge($base, $overrides));
    }
}
