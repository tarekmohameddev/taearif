<?php

namespace App\Models\Property;

use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDocument extends Model
{
    protected $fillable = [
        'property_id',
        'type',
        'title',
        'content',
        'attachments',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'attachments' => 'array',
        'meta' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
