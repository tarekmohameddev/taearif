<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AiUsageLog extends Model
{
    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'pass_type',
        'model',
        'provider',
        'tokens_in',
        'tokens_out',
        'latency_ms',
        'cost_micros',
        'success',
        'error_code',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];
}
