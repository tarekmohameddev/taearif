<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApiAboutSettings extends Model
{
    use HasFactory;

    protected $table = 'api_about_settings';

    protected $fillable = [
        'user_id',
        'title',
        'subtitle',
        'history',
        'mission',
        'vision',
        'image_path',
        'features',  // JSON column
    ];

    // Cast the features column to array
    protected $casts = [
        'features' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
