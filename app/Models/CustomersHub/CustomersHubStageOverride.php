<?php

namespace App\Models\CustomersHub;

use Illuminate\Database\Eloquent\Model;

class CustomersHubStageOverride extends Model
{
    protected $table = 'customers_hub_stage_overrides';

    protected $fillable = [
        'user_id',
        'stage_id',
        'stage_name_ar',
        'stage_name_en',
        'color',
        'order',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'order' => 'integer',
    ];
}

