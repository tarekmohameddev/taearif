<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'password_reset_logs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'method',
        'code',
        'used',
        'expires_at',
        'attempts',
        'blocked',
        'blocked_until',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'blocked_until' => 'datetime',
        'used' => 'boolean',
        'blocked' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
