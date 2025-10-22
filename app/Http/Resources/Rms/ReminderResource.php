<?php

namespace App\Http\Resources\Rms;

use Illuminate\Http\Resources\Json\JsonResource;

class ReminderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'snooze_until' => $this->snooze_until,
            'is_dismissed' => $this->is_dismissed,
            'rental_id' => $this->rental_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

