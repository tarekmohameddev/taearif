<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'email',
        'action',
        'previous_package',
        'new_package',
        'event_timestamp',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
    ];

    /**
     * Get the user that owns the membership change log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
