<?php

namespace App\Domain\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\User\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Admin Impersonation Model
 *
 * Tracks when admins impersonate tenant users
 * Provides complete audit trail for security & compliance
 */
class AdminImpersonation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin_impersonations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'user_id',
        'token_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'ip_address',
        'user_agent',
        'reason',
        'actions_count',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'actions_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who performed the impersonation.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * Get the user who was impersonated.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the Sanctum token used for impersonation.
     */
    public function token()
    {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }

    /**
     * Check if impersonation is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && is_null($this->ended_at);
    }

    /**
     * Check if impersonation has ended.
     *
     * @return bool
     */
    public function isEnded(): bool
    {
        return $this->status === 'ended' && !is_null($this->ended_at);
    }

    /**
     * Check if impersonation has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Get formatted duration.
     *
     * @return string
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            if ($this->isActive() && $this->started_at) {
                $seconds = now()->diffInSeconds($this->started_at);
            } else {
                return 'Unknown';
            }
        } else {
            $seconds = $this->duration_seconds;
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d hours %d minutes', $hours, $minutes);
        } elseif ($minutes > 0) {
            return sprintf('%d minutes %d seconds', $minutes, $secs);
        } else {
            return sprintf('%d seconds', $secs);
        }
    }

    /**
     * Calculate and update duration.
     *
     * @return void
     */
    public function calculateDuration(): void
    {
        if ($this->started_at && $this->ended_at) {
            $this->duration_seconds = $this->ended_at->diffInSeconds($this->started_at);
        }
    }

    /**
     * End the impersonation session.
     *
     * @return void
     */
    public function endSession(): void
    {
        $this->ended_at = now();
        $this->status = 'ended';
        $this->calculateDuration();
        $this->save();
    }

    /**
     * Mark as expired.
     *
     * @return void
     */
    public function markAsExpired(): void
    {
        $this->ended_at = now();
        $this->status = 'expired';
        $this->calculateDuration();
        $this->save();
    }

    /**
     * Increment actions count.
     *
     * @return void
     */
    public function incrementActions(): void
    {
        $this->increment('actions_count');
    }

    /**
     * Scope a query to only include active impersonations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->whereNull('ended_at');
    }

    /**
     * Scope a query to only include ended impersonations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnded($query)
    {
        return $query->whereIn('status', ['ended', 'expired', 'revoked'])
                     ->whereNotNull('ended_at');
    }

    /**
     * Scope a query by admin.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $adminId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope a query by user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        if ($startDate) {
            $query->where('started_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('started_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\AdminImpersonationFactory::new();
    }
}

