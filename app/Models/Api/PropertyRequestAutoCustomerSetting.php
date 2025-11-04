<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyRequestAutoCustomerSetting extends Model
{
    use HasFactory;

    protected $table = 'property_request_auto_customer_settings';

    protected $fillable = [
        'user_id',
        'auto_create_customer',
        'default_stage_id',
    ];

    protected $casts = [
        'auto_create_customer' => 'boolean',
    ];

    /**
     * Relation with User (Tenant)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation with Default Stage
     */
    public function defaultStage()
    {
        return $this->belongsTo(UserApiCustomerStage::class, 'default_stage_id');
    }

    /**
     * Get settings for a specific tenant, or return default values
     */
    public static function getForTenant(int $userId): array
    {
        $settings = self::where('user_id', $userId)->first();

        if (!$settings) {
            return [
                'auto_create_customer' => false,
                'default_stage_id' => null,
            ];
        }

        return [
            'auto_create_customer' => $settings->auto_create_customer,
            'default_stage_id' => $settings->default_stage_id,
            'default_stage' => $settings->defaultStage,
        ];
    }
}

