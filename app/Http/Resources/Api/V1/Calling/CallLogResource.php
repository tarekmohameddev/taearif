<?php

namespace App\Http\Resources\Api\V1\Calling;

use Illuminate\Http\Resources\Json\JsonResource;

class CallLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'direction'        => $this->direction,
            'status'           => $this->status,
            'to_e164'          => $this->to_e164,
            'from_e164'        => $this->from_e164,
            'fail_reason'      => $this->fail_reason,
            'answered_at'      => $this->answered_at?->toIso8601String(),
            'ended_at'         => $this->ended_at?->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'customer'         => $this->when($this->relationLoaded('customer') && $this->customer, fn() => [
                'id'           => $this->customer->id,
                'name'         => $this->customer->name,
                'phone_number' => $this->customer->phone_number,
            ]),
            'agent' => $this->when($this->relationLoaded('agent') && $this->agent, fn() => [
                'id'       => $this->agent->id,
                'name'     => trim(($this->agent->first_name ?? '') . ' ' . ($this->agent->last_name ?? ''))
                    ?: ($this->agent->company_name ?? $this->agent->username),
                'username' => $this->agent->username,
            ]),
            'recording' => $this->when($this->relationLoaded('recording') && $this->recording, fn() => [
                'status'           => $this->recording->status,
                'duration_seconds' => $this->recording->duration_seconds,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
