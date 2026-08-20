<?php

namespace App\Http\Resources\Admin\Calling;

use Illuminate\Http\Resources\Json\JsonResource;

class TrunkResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                       => $this->id,
            'tenant_id'                => $this->tenant_id,
            'tenant'                   => $this->when($this->relationLoaded('tenant') && $this->tenant, fn() => [
                'id'    => $this->tenant->id,
                'name'  => $this->tenant->company_name
                    ?: trim(($this->tenant->first_name ?? '') . ' ' . ($this->tenant->last_name ?? ''))
                    ?: $this->tenant->username,
                'email' => $this->tenant->email,
            ]),
            'name'                     => $this->name,
            'type'                     => $this->type,
            'ownership'                => $this->ownership,
            'registration_mode'        => $this->registration_mode,
            'asterisk_endpoint_prefix' => $this->asterisk_endpoint_prefix,
            'status'                   => $this->status,
            'status_checked_at'        => $this->status_checked_at?->toIso8601String(),
            'meta'                     => $this->meta,
            'sim_lines_count'          => $this->when(isset($this->sim_lines_count), $this->sim_lines_count),
            'sim_lines'                => $this->when($this->relationLoaded('simLines'), fn() =>
                $this->simLines->map(fn($l) => [
                    'id'                => $l->id,
                    'label'             => $l->label,
                    'msisdn'            => $l->msisdn,
                    'asterisk_endpoint' => $l->asterisk_endpoint,
                    'is_active'         => $l->is_active,
                ])
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
