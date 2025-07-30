<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiCustomerInquiry extends Model
{
    protected $table = 'api_customer_inquiry';

    protected $fillable = [
        'user_id',
        'customer_id',
        'message',
        'inquiry_type',
        'property_type',
        'budget',
        'location',
    ];

    // Relationships (optional)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}