<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaAutomationRule extends Model
{
    protected $table = 'wa_automation_rules';

    protected $fillable = [
        'user_id',
        'wa_number_id',
        'name',
        'description',
        'trigger',
        'delay_minutes',
        'template_id',
        'is_active',
        'triggered_count',
        'success_count',
        'last_triggered_at',
        'meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'meta' => 'array',
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(WaTemplate::class, 'template_id');
    }
}
