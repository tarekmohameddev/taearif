<?php

// namespace App\Models\User\Api;
namespace App\Models\Api;

use App\Models\User;
use App\Support\PropertyRequestFilterOptionsCache;
use Illuminate\Database\Eloquent\Model;

class ApiUserCategorySetting extends Model
{
    protected $table = 'api_user_category_settings';

    protected static function booted(): void
    {
        $forgetFilterCaches = function (ApiUserCategorySetting $model): void {
            $id = (int) $model->user_id;
            if ($id > 0) {
                PropertyRequestFilterOptionsCache::forgetFilterDataForOwner($id);
            }
        };

        static::saved($forgetFilterCaches);
        static::deleted($forgetFilterCaches);
    }
    protected $fillable = ['user_id', 'category_id', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\User\RealestateManagement\ApiUserCategory::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

}
