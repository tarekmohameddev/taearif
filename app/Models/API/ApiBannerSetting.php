<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApiBannerSetting extends Model
{
    use HasFactory;

    protected $table = 'api_banner_settings';

    protected $fillable = [
        'user_id',
        'banner_type',
        'static',
        'slider',
        'common',
        'status'
    ];

    protected $casts = [
        'static' => 'array',
        'slider' => 'array',
        'common' => 'array',
        'status' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
