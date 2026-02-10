<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Reminder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reminders';

    protected $fillable = [
        'user_id',
        'customer_id',
        'reminder_type_id',
        'title',
        'description',
        'datetime',
        'priority',
        'status',
        'notes',
        'source',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'priority' => 'integer',
    ];

    /**
     * Relationship: Reminder belongs to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Reminder belongs to ApiCustomer
     */
    public function customer()
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }

    /**
     * Relationship: Reminder belongs to ReminderType
     */
    public function reminderType()
    {
        return $this->belongsTo(ReminderType::class, 'reminder_type_id');
    }

    /**
     * Accessor: Get priority label in English
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            0 => 'Low',
            1 => 'Medium',
            2 => 'High',
            default => 'Unknown',
        };
    }

    /**
     * Accessor: Get priority label in Arabic
     */
    public function getPriorityLabelArAttribute(): string
    {
        return match ($this->priority) {
            0 => 'منخفضة',
            1 => 'متوسطة',
            2 => 'عالية',
            default => 'غير معروف',
        };
    }

    /**
     * Accessor: Get status label in English
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Accessor: Get status label in Arabic
     */
    public function getStatusLabelArAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'قيد الانتظار',
            'completed' => 'مكتمل',
            'overdue' => 'متأخر',
            'cancelled' => 'ملغي',
            default => 'غير معروف',
        };
    }

    /**
     * Accessor: Check if reminder is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }
        return $this->datetime && $this->datetime->isPast();
    }

    /**
     * Accessor: Get days until due (can be negative if overdue)
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->datetime) {
            return null;
        }
        return Carbon::now()->diffInDays($this->datetime, false);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope: Filter by reminder type
     */
    public function scopeForReminderType($query, $reminderTypeId)
    {
        return $query->where('reminder_type_id', $reminderTypeId);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeForStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by priority
     */
    public function scopeForPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $dateFrom, $dateTo)
    {
        if ($dateFrom) {
            $query->whereDate('datetime', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('datetime', '<=', $dateTo);
        }
        return $query;
    }

    /**
     * Scope: Search in title, description, customer name
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%")
              ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                  $customerQuery->where('name', 'like', "%{$searchTerm}%");
              });
        });
    }
}
