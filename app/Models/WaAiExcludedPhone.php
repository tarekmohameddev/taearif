<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class WaAiExcludedPhone extends Model
{
    protected $table = 'wa_ai_excluded_phones';

    protected $fillable = [
        'user_id',
        'wa_number_id',
        'phone',
    ];
}
