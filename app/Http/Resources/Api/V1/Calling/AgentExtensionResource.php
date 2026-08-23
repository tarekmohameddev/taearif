<?php

namespace App\Http\Resources\Api\V1\Calling;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentExtensionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'sip_username' => $this->sip_username,
            'extension'    => $this->extension,
            'is_active'    => $this->is_active,
            'user'         => $this->when($this->relationLoaded('user'), fn() => [
                'id'       => $this->user->id,
                'name'     => trim(($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? ''))
                    ?: ($this->user->company_name ?? $this->user->username),
                'username' => $this->user->username,
                'email'    => $this->user->email,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
