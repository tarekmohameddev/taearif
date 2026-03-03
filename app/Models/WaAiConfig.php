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
        'timezone',
        'scenarios',
        'tone',
        'language',
        'custom_instructions',
        'fallback_to_human',
        'fallback_delay',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'enabled' => 'boolean',
        'business_hours_only' => 'boolean',
        'scenarios' => 'array',
        'fallback_to_human' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
