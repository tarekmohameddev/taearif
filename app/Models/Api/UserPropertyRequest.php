<?php

namespace App\Models\Api;

use App\Domain\CustomersHub\Services\CustomersHubCacheVersion;
use App\Models\ApiCustomer;
use App\Models\CustomersHub\CrmHubNote;
use App\Models\CustomersHub\CustomersHubStage;
use App\Models\User;
use App\Support\PropertyRequestFilterOptionsCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class UserPropertyRequest extends Model
{
    use HasFactory;

    protected $table = 'users_property_requests';

    protected static function booted(): void
    {
        static::creating(function (UserPropertyRequest $model): void {
            if (!isset($model->customers_hub_stage_id) || $model->customers_hub_stage_id === null) {
                $model->customers_hub_stage_id = CustomersHubStage::getDefaultStageId();
            }

            // Force medium priority at creation time (Customers Hub derives priority from seriousness)
            $model->seriousness = 'خلال 3 أشهر';
        });

        $forgetStats = function (UserPropertyRequest $model): void {
            $ids = array_filter(
                [$model->getOriginal('user_id'), $model->user_id],
                fn ($v) => $v !== null
            );
            $cacheVersion = app(CustomersHubCacheVersion::class);
            foreach (array_unique($ids) as $id) {
                Cache::forget('property_requests_statistics_' . $id);
                PropertyRequestFilterOptionsCache::forgetFilterDataForOwner($id);
                Cache::forget("property_request_filter_options_meta_{$id}");
                Cache::forget("ch:reqs:filter-options:{$id}");
                // Bump the Customers Hub cache version so list/stats/count caches
                // (parameterized by arbitrary filter/pagination combos, so they
                // cannot be individually forgotten) become invalid immediately.
                $cacheVersion->bump((int) $id);
            }
        };

        static::saved($forgetStats);
        static::deleted($forgetStats);
    }
// wants_similar_offers purchase_method region
    protected $fillable = [
        'user_id',
        'customer_id',
        'source',
        'referral_source',
        'region', // nullable
        'purpose',
        'property_type',
        'category_id',
        'city_id',
        'districts_id',
        'area_from',
        'area_to',
        'purchase_method', // nullable
        'budget_from',
        'budget_to',
        'seriousness',
        'purchase_goal',
        'wants_similar_offers', // nullable
        'full_name',
        'phone',
        'contact_on_whatsapp',
        'notes',
        'is_read',
        'is_archived',
        'is_ignored',
        'is_active',
        'status_id',
        'customers_hub_stage_id',
        'property_ids',
        'initial_property_id',
        'project_id',
        'responsible_employee_id',
        'inquiry_type',
        'currency',
        'bedrooms',
        'bathrooms',
        'furnished',
        'location',
        'country_code',
        'region_code',
        'city',
        'district',
        'latitude',
        'longitude',
        'location_confidence',
        'lang',
        'detected_entities_json',
    ];

    protected $casts = [
        'wants_similar_offers' => 'boolean',
        'contact_on_whatsapp'  => 'boolean',
        'budget_from'          => 'float',
        'budget_to'            => 'float',
        'area_from'            => 'integer',
        'area_to'              => 'integer',
        'is_read'              => 'boolean',
        'is_archived'          => 'boolean',
        'is_ignored'           => 'boolean',
        'status_id'            => 'integer',
        'property_ids'         => 'array',
    ];

    protected $hidden = [
        'statusOption',
        'customer',
        'responsibleEmployee',
        'status_id',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusOption()
    {
        return $this->belongsTo(\App\Models\PropertyRequestStatus::class, 'status_id');
    }

    public function customer()
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }

    public function hubNotes()
    {
        return $this->morphMany(CrmHubNote::class, 'noteable');
    }

    /**
     * First linked customer. Eager load 'customer' then use $request->customer.
     */
    public function getCustomerAttribute(): ?ApiCustomer
    {
        return $this->getRelationValue('customer') ?? $this->customer()->first();
    }

    public function district()
    {
        return $this->belongsTo(\App\Models\User\UserDistrict::class, 'districts_id');
    }

    public function initialProperty()
    {
        return $this->belongsTo(\App\Models\User\RealestateManagement\Property::class, 'initial_property_id');
    }

    public function project()
    {
        return $this->belongsTo(\App\Models\User\RealestateManagement\Project::class, 'project_id');
    }

    /**
     * Ensure property_ids is always returned as an array (not a JSON string).
     */
    public function getPropertyIdsAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Get district name: use relation (UserDistrict) if loaded, not the string attribute 'district'
        $districtRelation = $this->getRelationValue('district');
        $districtName = ($districtRelation && is_object($districtRelation))
            ? $districtRelation->name_ar
            : $this->getAttribute('district');
        
        // Get property type Arabic translation
        $propertyTypeAr = $this->getPropertyTypeArabic();
        
        // Get customer_id from the customer relationship or direct attribute
        $customerId = $this->customer_id ?? ($this->customer ? $this->customer->id : null);
        
        // Insert districtName right after districts_id and customer_id after user_id
        $result = [];
        foreach ($array as $key => $value) {
            $result[$key] = $value;
            if ($key === 'user_id') {
                $result['customer_id'] = $customerId;
            }
            if ($key === 'districts_id') {
                $result['districtName'] = $districtName;
            }
            if ($key === 'property_type') {
                $result['property_type_ar'] = $propertyTypeAr;
            }
        }
        
        // Fallback: if user_id wasn't in the array, add customer_id anyway
        if (!isset($result['customer_id'])) {
            $result['customer_id'] = $customerId;
        }
        
        // Fallback: if districts_id wasn't in the array, add districtName anyway
        if (!isset($result['districtName'])) {
            $result['districtName'] = $districtName;
        }
        
        // Fallback: if property_type wasn't in the array, add property_type_ar anyway
        if (!isset($result['property_type_ar'])) {
            $result['property_type_ar'] = $propertyTypeAr;
        }

        $result['status'] = $this->statusOption
            ? [
                'id' => $this->statusOption->id,
                'name_ar' => $this->statusOption->name_ar,
                'name_en' => $this->statusOption->name_en,
            ]
            : null;

        $result['employee'] = $this->formatEmployeePayload();

        return $result;
    }

    protected function formatEmployeePayload(): ?array
    {
        $customer = $this->customer;

        if (!$customer || !$customer->responsibleEmployee) {
            return null;
        }

        $employee = $customer->responsibleEmployee;
        $name = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
        if ($name === '') {
            $name = $employee->email;
        }

        return [
            'id' => $employee->id,
            'name' => $name ?: null,
            'whatsapp_number' => $employee->activeWhatsappUser ? $employee->activeWhatsappUser->number : null,
        ];
    }

    /**
     * Scope a query to filter by status_id.
     */
    public function scopeByStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    public function getStatusNameAttribute(): ?string
    {
        if ($this->relationLoaded('statusOption') && $this->statusOption) {
            return $this->statusOption->name_ar ?? $this->statusOption->name_en;
        }

        if ($this->statusOption) {
            return $this->statusOption->name_ar ?? $this->statusOption->name_en;
        }

        return null;
    }

    /**
     * Get Arabic translation for property type
     */
    protected function getPropertyTypeArabic(): ?string
    {
        if (!$this->property_type) {
            return null;
        }

        $map = [
            'Commercial' => 'تجاري',
            'Residential' => 'سكني',
            'Industrial' => 'صناعي',
            'Agricultural' => 'زراعي',
        ];

        return $map[$this->property_type] ?? $this->property_type;
    }
}
