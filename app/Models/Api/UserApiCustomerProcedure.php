<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class UserApiCustomerProcedure extends Model
{
    use HasFactory;

    protected $table = 'users_api_customers_procedures';

    protected static function booted(): void
    {
        $forgetMeta = function (UserApiCustomerProcedure $model): void {
            $id = (int) $model->user_id;
            if ($id > 0) {
                Cache::forget("property_request_filter_options_meta_{$id}");
            }
        };

        static::saved($forgetMeta);
        static::deleted($forgetMeta);
    }
    protected $fillable = [
        'procedure_name',
        'user_id',
        'color',
        'icon',
        'order',
        'description',
        'is_active',
    ];

    /**
     * Relation with User
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customers()
    {
        return $this->hasMany(\App\Models\ApiCustomer::class, 'procedure_id');
    }

    public function getActiveCustomersCountAttribute()
    {
        return $this->customers()->where('is_active', true)->count();
    }

}
