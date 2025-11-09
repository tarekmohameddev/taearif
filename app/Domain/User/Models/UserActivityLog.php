<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domain\Admin\Models\Admin;

/**
 * User Activity Log Model
 *
 * Stores audit trail for admin actions on tenant accounts
 */
class UserActivityLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_activity_logs';

    /**
     * Indicates if the model should be timestamped.
     * Only created_at is present and handled manually.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'admin_id',
        'action',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the tenant user associated with the activity log.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who performed the action.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}

