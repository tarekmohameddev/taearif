<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardDailyVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_owner_id',
        'visited_on',
        'first_seen_at',
        'last_seen_at',
        'visits_count',
    ];

    protected $casts = [
        'visited_on' => 'date',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'visits_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenantOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_owner_id');
    }
}
