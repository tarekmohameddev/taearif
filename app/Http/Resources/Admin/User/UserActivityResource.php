<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Activity Resource
 */
class UserActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'description' => $this->description,
            'performed_by' => [
                'id' => $this->admin_id,
                'name' => $this->admin ? trim(($this->admin->first_name ?? '') . ' ' . ($this->admin->last_name ?? '')) : 'System',
                'email' => $this->admin?->email,
            ],
            'metadata' => $this->metadata,
            'request' => [
                'ip' => $this->ip_address,
                'user_agent' => $this->user_agent,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

