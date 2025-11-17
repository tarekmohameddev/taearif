<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
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
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'source' => $this->source,
            'status' => $this->status,
            'notes' => $this->notes,
            'custom_fields' => $this->custom_fields,
            'stage' => $this->when($this->relationLoaded('stage'), function () {
                return $this->stage ? [
                    'id' => $this->stage->id,
                    'uuid' => $this->stage->uuid,
                    'name' => $this->stage->name,
                    'slug' => $this->stage->slug,
                    'color' => $this->stage->color,
                ] : null;
            }),
            'assigned_admin' => $this->when($this->relationLoaded('assignedAdmin'), function () {
                return $this->assignedAdmin ? [
                    'id' => $this->assignedAdmin->id,
                    'uuid' => $this->assignedAdmin->uuid,
                    'username' => $this->assignedAdmin->username,
                    'email' => $this->assignedAdmin->email,
                ] : null;
            }),
            'converted_user' => $this->when($this->relationLoaded('convertedUser'), function () {
                return $this->convertedUser ? [
                    'id' => $this->convertedUser->id,
                    'username' => $this->convertedUser->username,
                    'email' => $this->convertedUser->email,
                ] : null;
            }),
            'converted_at' => $this->converted_at?->toIso8601String(),
            'activities_count' => $this->when($this->relationLoaded('activities'), function () {
                return $this->activities->count();
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

