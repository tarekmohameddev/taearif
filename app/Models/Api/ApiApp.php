<?php

namespace App\Models\Api;

use App\Models\Api\ApiInstallation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\BillingType;

class ApiApp extends Model
{
    use HasFactory;
    protected $table = 'api_apps';

    protected $fillable = [
        'name',
        'description',
        'price',
        'type',
        'rating',
        'img',
        'billing_type',
        'trial_days',

    ];
    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'float',
        'trial_days' => 'integer',
        'billing_type' => BillingType::class,
    ];
    protected $attributes = [
        'type' => 'marketplace',
        'rating' => 0,
    ];
    public function installations()
    {
        return $this->hasMany(ApiInstallation::class);
    }
}
