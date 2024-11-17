<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroStatic extends Model
{
    use HasFactory;

    protected $table = "user_hero_statics";

    protected $fillable = [
        'language_id',
        'user_id',
        'img',
        'title',
        'subtitle',
        'btn_name',
        'btn_url',
        'hero_text',
        'secound_btn_name',
        'secound_btn_url',
        'designation'
    ];

    public function staticVersionLang()
    {
        return $this->belongsTo(Language::class);
    }
}
