<?php

declare(strict_types=1);

namespace App\Domain\Calling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class CallSetting extends Model
{
    protected $table = 'call_settings';

    protected $fillable = [
        'tenant_id',
        'enabled',
        'record_by_default',
        'play_recording_announcement',
        'max_channels',
    ];

    protected $casts = [
        'enabled'                      => 'boolean',
        'record_by_default'            => 'boolean',
        'play_recording_announcement'  => 'boolean',
        'max_channels'                 => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
