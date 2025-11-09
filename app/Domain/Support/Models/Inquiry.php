<?php

namespace App\Domain\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\User\Models\User;
use App\Models\ApiCustomer;
use Illuminate\Support\Str;

/**
 * Inquiry Model
 *
 * Represents customer inquiries/support tickets
 * Uses existing api_customer_inquiry table
 */
class Inquiry extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\InquiryFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_customer_inquiry';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'phone_number',
        'message',
        'inquiry_type',
        'property_type',
        'budget',
        'location',
        'currency',
        'bedrooms',
        'bathrooms',
        'min_area_sqm',
        'max_area_sqm',
        'furnished',
        'urgency',
        'country_code',
        'region_code',
        'region_name',
        'city',
        'district',
        'latitude',
        'longitude',
        'location_confidence',
        'source_channel',
        'lang',
        'detected_entities_json',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budget' => 'decimal:2',
        'min_area_sqm' => 'decimal:2',
        'max_area_sqm' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'location_confidence' => 'decimal:2',
        'furnished' => 'boolean',
        'detected_entities_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();
    }

    /**
     * Get the route key for the model.
     * Using ID for now, can be changed to UUID if column is added
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Get the user that owns the inquiry.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the customer associated with the inquiry.
     */
    public function customer()
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }

    /**
     * Get the admin assigned to this inquiry.
     */
    public function assignedAdmin()
    {
        return $this->belongsTo(\App\Domain\Admin\Models\Admin::class, 'assigned_to');
    }

    /**
     * Scope a query to only include inquiries by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('inquiry_type', $type);
    }

    /**
     * Scope a query to only include inquiries by tenant.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTenant($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

