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
        'phone_number',
        'message',
        'inquiry_type',
        'property_type',
        'budget',
        'location',
        
        // Monetary / preference fields
        'currency',
        'bedrooms',
        'bathrooms',
        'min_area_sqm',
        'max_area_sqm',
        'furnished',
        'urgency',
        
        // Normalized location fields
        'country_code',
        'region_code',
        'region_name',
        'city',
        'district',
        'latitude',
        'longitude',
        'location_confidence',
        
        // Meta fields
        'source_channel',
        'lang',
        'detected_entities_json',
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