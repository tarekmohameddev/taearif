<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaTemplate extends Model
{
    protected $table = 'wa_templates';

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'category',
        'variables',
        'is_active',
        'language',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(WaAutomationRule::class, 'template_id');
    }
}
