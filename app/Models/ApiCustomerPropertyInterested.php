<?php

namespace App\Models;

use App\Models\User;
use App\Models\ApiCustomer;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\ApiUserCategory;


class ApiCustomerPropertyInterested extends Model
{
    protected $table = 'api_customer_property_interested';

    protected $fillable = [
        'user_id',
        'customer_id',
        'property_id',
        'category_id',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
    public function customer()
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function category()
    {
        return $this->belongsTo(ApiUserCategory::class, 'category_id');
    }

}
