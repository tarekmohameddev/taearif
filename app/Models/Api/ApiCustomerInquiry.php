<?php

namespace App\Models\Api;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\CustomersHub\Services\CustomersHubCacheVersion;
use App\Models\ApiCustomer;
use App\Models\CustomersHub\CrmHubNote;
use App\Models\CustomersHub\CustomersHubStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'status_id',
        'stage_id',
        'responsible_employee_id',
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
        'is_read',
        'is_archived',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApiCustomerInquiry $model): void {
            if (!isset($model->stage_id) || $model->stage_id === null) {
                $model->stage_id = CustomersHubStage::getDefaultStageId();
            }
        });

        // Inquiries feed the same Customers Hub UNION query as property requests,
        // so bump the tenant's cache version on every create/update/delete so
        // list/stats/count caches (which can't be individually forgotten, since
        // they're parameterized by arbitrary filter/pagination combos) invalidate
        // immediately instead of waiting for TTL expiry.
        $bumpCacheVersion = function (ApiCustomerInquiry $model): void {
            $ids = array_filter(
                [$model->getOriginal('user_id'), $model->user_id],
                fn ($v) => $v !== null
            );
            $cacheVersion = app(CustomersHubCacheVersion::class);
            foreach (array_unique($ids) as $id) {
                $cacheVersion->bump((int) $id);
            }
        };

        static::saved($bumpCacheVersion);
        static::deleted($bumpCacheVersion);
    }

    protected $casts = [
        'is_read' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_employee_id');
    }

    public function hubNotes()
    {
        return $this->morphMany(CrmHubNote::class, 'noteable');
    }
}
