<?php

declare(strict_types=1);

namespace App\Domain\Calling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

final class CallTrunk extends Model
{
    use SoftDeletes;

    protected $table = 'call_trunks';

    // Credentials are encrypted in the application layer before save;
    // we do NOT use Laravel's 'encrypted' cast here because we need
    // to selectively decrypt only on write operations (never on API read).
    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'ownership',
        'registration_mode',
        'asterisk_endpoint_prefix',
        'status',
        'status_checked_at',
        'credentials_encrypted',
        'meta',
    ];

    protected $casts = [
        'meta'              => 'array',
        'status_checked_at' => 'datetime',
    ];

    // Never expose credentials or meta in serialisation by default
    protected $hidden = ['credentials_encrypted'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function simLines(): HasMany
    {
        return $this->hasMany(CallSimLine::class, 'trunk_id');
    }

    public function isRegistered(): bool
    {
        return $this->status === 'registered';
    }
}
