<?php

namespace App\Domain\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Domain\Admin\Models\Role;
use Database\Factories\AdminFactory;

/**
 * Admin Model
 *
 * Represents admin users in the system
 * Uses Sanctum for API authentication
 */
class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admins';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'image',
        'status',
        'last_login_at',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_login_at' => 'datetime',
        'permissions' => 'array',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate UUID on creation if not provided
        static::creating(function ($admin) {
            if (empty($admin->uuid)) {
                $admin->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the role that owns the admin.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if admin is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === true || $this->status === 1;
    }

    /**
     * Check if admin has a specific permission
     * 
     * Priority: Employee-specific permissions > Role permissions
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // First check employee-specific permissions (overrides)
        if ($this->permissions && is_array($this->permissions)) {
            if (in_array($permission, $this->permissions)) {
                return true;
            }
        }
        
        // Fall back to role permissions
        if (!$this->role) {
            return false;
        }

        $rolePermissions = is_array($this->role->permissions)
            ? $this->role->permissions
            : json_decode($this->role->permissions, true);

        if (!is_array($rolePermissions)) {
            return false;
        }

        return in_array($permission, $rolePermissions);
    }

    /**
     * Get all permissions for this admin (employee-specific + role permissions merged)
     * 
     * Returns employee-specific permissions if set, otherwise role permissions
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        
        // Start with role permissions
        if ($this->role && $this->role->permissions) {
            $rolePermissions = is_array($this->role->permissions)
                ? $this->role->permissions
                : json_decode($this->role->permissions, true);
            
            if (is_array($rolePermissions)) {
                $permissions = $rolePermissions;
            }
        }
        
        // Override with employee-specific permissions if they exist
        if ($this->permissions && is_array($this->permissions)) {
            $permissions = $this->permissions;
        }
        
        return array_unique($permissions);
    }

    /**
     * Get admin's full name
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Scope a query to only include active admins.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include admins by role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $roleId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AdminFactory
    {
        return AdminFactory::new();
    }
}

