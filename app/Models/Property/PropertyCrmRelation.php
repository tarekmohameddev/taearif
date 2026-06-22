<?php

namespace App\Models\Property;

use App\Models\Api\Crm\CrmRequest;
use App\Models\ApiCustomer;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyCrmRelation extends Model
{
    protected $fillable = [
        'property_id',
        'request_id',
        'relation_type',
        'employee_id',
        'customer_id',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public const TYPE_AI_MATCHED = 'ai_matched';
    public const TYPE_MANUALLY_ADDED = 'manually_added';
    public const TYPE_SENT_TO_CUSTOMER = 'sent_to_customer';

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(CrmRequest::class, 'request_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }
}
