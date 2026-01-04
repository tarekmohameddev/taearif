<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappAddonPlan extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_addon_plans';

    protected $fillable = [
        'name',
        'price',
        'duration',
        'duration_unit',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
