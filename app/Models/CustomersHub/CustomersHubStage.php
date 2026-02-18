<?php

namespace App\Models\CustomersHub;

use Illuminate\Database\Eloquent\Model;

class CustomersHubStage extends Model
{
    protected $table = 'customers_hub_stages';

    protected $fillable = [
        'stage_id',
        'stage_name_ar',
        'stage_name_en',
        'color',
        'order',
        'description',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Return the stage_id string for the default stage (customers_hub_stages.id = 1).
     * Used for backfill and for new requests/inquiries when no stage is set.
     * Returns null if no row with id = 1 or it is inactive (Unassigned).
     */
    public static function getDefaultStageId(): ?string
    {
        $row = static::where('id', 1)->where('is_active', true)->first(['stage_id']);

        return $row ? $row->stage_id : null;
    }
}
