<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PropertyExternalLink extends Model
{
    protected $table = 'property_external_links';

    protected $fillable = [
        'property_id',
        'user_id',
        'platform',
        'url',
        'label',
        'active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'active' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
