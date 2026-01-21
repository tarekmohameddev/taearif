<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class UserApiCustomerType extends Model
{
    use HasFactory;

    protected $table = 'users_api_customers_types';

    protected static function booted(): void
    {
        $forgetMeta = function (UserApiCustomerType $model): void {
            $id = (int) $model->user_id;
            if ($id > 0) {
                Cache::forget("property_request_filter_options_meta_{$id}");
            }
        };

        static::saved($forgetMeta);
        static::deleted($forgetMeta);
    }

    protected $fillable = [
        'user_id',
        'name',
        'value',
        'color',
        'icon',
        'order',
        'is_active',
    ];

    /**
     * Relation with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
