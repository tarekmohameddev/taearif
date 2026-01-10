<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReminderType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reminder_types';

    protected $fillable = [
        'user_id',
        'name',
        'name_ar',
        'description',
        'color',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Relationship: ReminderType belongs to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: ReminderType has many Reminders
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Scope: Get active reminder types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Accessor: Get reminders count
     */
    public function getRemindersCountAttribute()
    {
        if (!isset($this->attributes['reminders_count'])) {
            return $this->reminders()->count();
        }
        return $this->attributes['reminders_count'];
    }
}
