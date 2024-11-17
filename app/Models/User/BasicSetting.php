<?php

namespace App\Models\User;

use App\Models\Timezone;
use Illuminate\Database\Eloquent\Model;

class BasicSetting extends Model
{
    public $table = "user_basic_settings";

    protected $fillable = [
        'favicon',
        'logo',
        'cv',
        'base_color',
        'theme',
        'user_id',
        'timezone'
    ];

    public function language()
    {
        return $this->hasMany('App\Models\User\Language', 'user_id');
    }
    public function timezoneinfo()
    {
        return $this->belongsTo(Timezone::class,'timezone');
    }
}
