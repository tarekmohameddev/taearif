<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingMeter extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'meter_type',
        'meter_number',
    ];

    public const TYPE_WATER = 'water';
    public const TYPE_ELECTRICITY = 'electricity';

    /**
     * Get the building that owns the meter.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
