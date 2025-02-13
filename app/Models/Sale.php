<?php

namespace App\Models;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\RealestateManagement\PropertyContent;


class Sale extends Model
{
    use HasFactory;

    protected $table = 'sales';

    protected $fillable = [
        'property_id',
        'user_id',
        'contract_id',
        'sale_price',
        'sale_date',
        'status',
    ];

        // Define relationships
        public function property()
        {
            return $this->belongsTo(Property::class, 'property_id');
        }

        public function user()
        {
            return $this->belongsTo(User::class, 'user_id');
        }
        public function contract()
        {
            return $this->belongsTo(Contract::class);
        }

        public function customer()
        {
            return $this->hasOneThrough(Customer::class, Contract::class, 'id', 'id', 'contract_id', 'customer_id');
        }

        public function contents()
        {
            return $this->belongsTo(PropertyContent::class, 'property_id');
        }
}
