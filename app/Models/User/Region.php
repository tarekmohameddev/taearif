<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;
    protected $fillable = ['name_en', 'name_ar'];

    public function governorates() {
        return $this->hasMany(Governorate::class);
    }

}
