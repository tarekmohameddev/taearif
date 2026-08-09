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
        'meta_template_id',
        'name',
        'category',
        'status',
        'language',
        'content',
        'variables',
        'components',
        'namespace',
        'is_active',
        'synced_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'variables' => 'array',
        'components' => 'array',
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
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
