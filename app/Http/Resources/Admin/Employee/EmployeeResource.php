<?php

namespace App\Http\Resources\Admin\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Employee Resource
 *
 * Transforms Admin model into JSON response for employee management
 */
class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name . ' ' . $this->last_name),
            'image' => $this->image ? asset('assets/admin/img/propics/' . $this->image) : null,
            'status' => (bool) $this->status,
            'status_text' => $this->status ? 'active' : 'inactive',
            'role' => [
                'id' => $this->role?->id,
                'name' => $this->role?->name,
                'permissions' => $this->role?->permissions,
            ],
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

