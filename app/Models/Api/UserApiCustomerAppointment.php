<?php

namespace App\Models\Api;

use App\Models\User;
use App\Models\ApiCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserApiCustomerAppointment extends Model
{
    use HasFactory;
    protected $table = 'users_api_customers_appointments';
    protected $fillable = [
        'user_id',
        'customer_id',
        'title',
        'type', // e.g., meeting, call, follow-up
        'priority', // 1=low, 2=medium, 3=high
        'note',
        'datetime',
        'duration', // in minutes
    ];
    protected $casts = [
        'datetime' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function customer()
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }

    /**
     * Get the priority label.
     *
     * @return string
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            1 => 'Low',
            2 => 'Medium',
            3 => 'High',
            default => 'Unknown',
        };
    }
}
