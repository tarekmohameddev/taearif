<?php

// namespace App\Models\User\Api;
namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ApiUserCategorySetting extends Model
{
    protected $table = 'api_user_category_settings';
    protected $fillable = ['user_id', 'category_id', 'is_active'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\User\RealestateManagement\ApiUserCategory::class, 'category_id');
    }
}
