<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin Resource
 *
 * Transforms Admin model for API responses
 */
class AdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->email,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'image' => $this->image ? url($this->image) : null,
            'status' => $this->status ? 'active' : 'inactive',
            'role' => [
                'id' => $this->role?->id,
                'name' => $this->role?->name,
                'permissions' => $this->role?->permissions,
            ],
            // Only include if employee has custom permissions (omitted if null/using role defaults)
            'permissions' => $this->when($this->permissions !== null, $this->permissions),
            // Final permissions array (employee-specific if exists, otherwise role permissions)
            'effective_permissions' => $this->getAllPermissions(),
            // Super admin flag (role_id === null means super admin)
            'is_super_admin' => is_null($this->role_id),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

