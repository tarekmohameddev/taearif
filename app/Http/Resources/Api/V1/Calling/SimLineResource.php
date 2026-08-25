<?php

namespace App\Http\Resources\Api\V1\Calling;

use Illuminate\Http\Resources\Json\JsonResource;

class SimLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'label'              => $this->label,
            'msisdn'             => $this->msisdn,
            'port_index'         => $this->port_index,
            'is_active'          => $this->is_active,
            'dedicated_agent_id' => $this->user_id,
            'trunk'              => $this->when($this->relationLoaded('trunk') && $this->trunk, fn() => [
                'id'     => $this->trunk->id,
                'name'   => $this->trunk->name,
                'type'   => $this->trunk->type,
                'status' => $this->trunk->status,
            ]),
        ];
    }
}
