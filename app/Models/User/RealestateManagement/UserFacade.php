<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFacade extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function UserPropertyCharacteristic()
    {
        return $this->hasMany(UserPropertyCharacteristic::class);
    }

}
