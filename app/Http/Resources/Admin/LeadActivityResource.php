<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_completed' => $this->completed_at !== null,
            'lead' => $this->when($this->relationLoaded('lead'), function () {
                return [
                    'uuid' => $this->lead->uuid,
                    'name' => $this->lead->name,
                    'email' => $this->lead->email,
                ];
            }),
            'admin' => $this->when($this->relationLoaded('admin'), function () {
                return [
                    'id' => $this->admin->id,
                    'uuid' => $this->admin->uuid,
                    'username' => $this->admin->username,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

