<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneralSetting extends Model
{
    use HasFactory;

    protected $table = 'api_general_settings';

    protected $fillable = [
        'user_id',
        'site_name',
        'tagline',
        'description',
        'logo',
        'favicon',
        'maintenance_mode',
        'show_breadcrumb',
        'show_properties',
        'additional_settings',
        'color',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'show_breadcrumb' => 'boolean',
        'show_properties' => 'boolean',
        'additional_settings' => 'array',
    ];

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
