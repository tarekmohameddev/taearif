<?php

namespace App\Models;

use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvertisingImport extends Model
{
    protected $fillable = [
        'user_id',
        'source_url',
        'platform',
        'status',
        'raw_data',
        'property_id',
        'error_message',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
