<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaAiConfig extends Model
{
    protected $table = 'wa_ai_configs';

    protected $fillable = [
        'user_id',
        'wa_number_id',
        'enabled',
        'business_hours_only',
        'business_hours_start',
        'business_hours_end',
        'business_hours',
        'timezone',
        'scenarios',
        'tone',
        'language',
        'custom_instructions',
        'goal',
        'autonomy_level',
        'reply_length_target',
        'confidence_threshold',
        'groundedness_threshold',
        'escalation_rules',
        'disclose_as_assistant',
        'assistant_name',
        'fallback_to_human',
        'fallback_delay',
        'monthly_token_budget',
        'playbook',
        'max_tokens_per_turn',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'enabled'                => 'boolean',
        'business_hours_only'    => 'boolean',
        'business_hours'         => 'array',
        'scenarios'              => 'array',
        'escalation_rules'       => 'array',
        'disclose_as_assistant'  => 'boolean',
        'confidence_threshold'   => 'integer',
        'groundedness_threshold' => 'integer',
        'reply_length_target'    => 'integer',
        'fallback_to_human'      => 'boolean',
        'monthly_token_budget'   => 'integer',
        'playbook'               => 'array',
        'max_tokens_per_turn'    => 'integer',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waNumber(): BelongsTo
    {
        return $this->belongsTo(WaNumber::class, 'wa_number_id');
    }
}
