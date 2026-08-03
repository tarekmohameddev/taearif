<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AiEvalRun extends Model
{
    protected $table = 'ai_eval_runs';

    protected $fillable = [
        'user_id',
        'run_id',
        'git_commit',
        'scores',
        'per_turn_results',
        'total_turns',
        'passed_turns',
        'passed',
        'regression_diff',
    ];

    protected $casts = [
        'scores'           => 'array',
        'per_turn_results' => 'array',
        'passed'           => 'boolean',
    ];
}
