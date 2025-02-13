<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\RealestateManagement\Property;


class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'property_id',
        'user_id',
        'scheduled_at',
        'booking_status',
        'booking_type',
        'notes',
        'amount',
        'payment_method',
        'payment_date',
        'payment_status',
        'payment_details',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
