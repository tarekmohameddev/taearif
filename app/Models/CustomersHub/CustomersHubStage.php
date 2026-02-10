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
}
