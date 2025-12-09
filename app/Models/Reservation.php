<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'type',
        'status',
        'customer_name',
        'customer_phone',
        'desired_date',
        'notes',
        'deposit_amount',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'desired_date' => 'date',
        'deposit_amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
