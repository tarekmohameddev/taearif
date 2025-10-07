<?php

namespace App\Models\Api;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ApiCustomer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * Class ApiCustomerInquiry
 * Represents customer inquiries in the API.
 */

class ApiCustomerInquiry extends Model
{
    use HasFactory;

    protected $table = 'api_customer_inquiry';

    //  `id`, `user_id`, `customer_id`, `message`, `inquiry_type`, `property_type`, `budget`, `location`,
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
