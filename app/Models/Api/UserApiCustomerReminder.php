<?php

namespace App\Models\Api;

use App\Models\User;
use App\Models\ApiCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserApiCustomerReminder extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_api_customers_reminders';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'title',
        'priority',
        'datetime',
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
     * Accessor: Get priority label (Low, Medium, High)
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
