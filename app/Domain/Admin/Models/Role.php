<?php

namespace App\Domain\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Role Model
 *
 * Represents admin roles with permissions
 */
class Role extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\RoleFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admins for the role.
     */
    public function admins()
    {
        return $this->hasMany(Admin::class, 'role_id');
    }

    /**
     * Check if role has a specific permission
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = is_array($this->permissions)
            ? $this->permissions
            : json_decode($this->permissions, true);

        if (!is_array($permissions)) {
            return false;
        }

        return in_array($permission, $permissions);
    }
}

