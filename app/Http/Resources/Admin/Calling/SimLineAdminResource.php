<?php

namespace App\Http\Resources\Admin\Calling;

use Illuminate\Http\Resources\Json\JsonResource;

class SimLineAdminResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'tenant_id'          => $this->tenant_id,
            'tenant'             => $this->when($this->relationLoaded('tenant') && $this->tenant, fn() => [
                'id'    => $this->tenant->id,
                'name'  => $this->tenant->company_name
                    ?: trim(($this->tenant->first_name ?? '') . ' ' . ($this->tenant->last_name ?? ''))
                    ?: $this->tenant->username,
                'email' => $this->tenant->email,
            ]),
            'trunk_id'           => $this->trunk_id,
            'trunk'              => $this->when($this->relationLoaded('trunk') && $this->trunk, fn() => [
                'id'     => $this->trunk->id,
                'name'   => $this->trunk->name,
                'type'   => $this->trunk->type,
                'status' => $this->trunk->status,
            ]),
            'label'              => $this->label,
            'msisdn'             => $this->msisdn,
            'asterisk_endpoint'  => $this->asterisk_endpoint,
            'port_index'         => $this->port_index,
            'is_active'          => $this->is_active,
            'dedicated_agent'    => $this->when($this->relationLoaded('dedicatedAgent') && $this->dedicatedAgent, fn() => [
                'id'       => $this->dedicatedAgent->id,
                'name'     => trim(($this->dedicatedAgent->first_name ?? '') . ' ' . ($this->dedicatedAgent->last_name ?? ''))
                    ?: ($this->dedicatedAgent->company_name ?? $this->dedicatedAgent->username),
                'username' => $this->dedicatedAgent->username,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
