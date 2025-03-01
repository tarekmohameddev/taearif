<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Governorate extends Model
{
    use HasFactory;
    protected $fillable = ['region_id', 'name_en', 'name_ar'];

    public function region() {
        return $this->belongsTo(Region::class);
    }

}
