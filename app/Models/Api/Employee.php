<?php

namespace App\Models\Api;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Employee extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'api_employees';

    protected $fillable = [
        'user_id','name','email','phone','password','active','last_login_at'
    ];
    protected $casts = [
        'active' => 'boolean',
        'last_login_at' => 'datetime',
    ];
    protected $hidden = ['password','remember_token'];

    public function tenant()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(\App\Models\Api\Role::class, 'api_employee_role', 'employee_id', 'role_id');
    }

    public function hasPermission(string $perm): bool
    {
        return $this->roles()->get()->flatMap(function ($role) {
            $raw = $role->permissions ?? [];
            $perms = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
            return $perms;
        })->contains($perm);
    }
}
