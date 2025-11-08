<?php

namespace App\Http\Resources\Admin\Daily;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * RMS Reminder Resource
 *
 * Transforms RMS Reminder model for API responses
 */
class RmsReminderResource extends JsonResource
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
            'id' => $this->id,
            'type' => $this->type,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'message' => $this->message,
            'status' => $this->status,
            'due_on' => $this->due_on?->format('Y-m-d'),
            'snooze_until' => $this->snooze_until?->format('Y-m-d'),
            'is_overdue' => $this->due_on ? $this->due_on->isPast() : false,
            'is_today' => $this->due_on ? $this->due_on->isToday() : false,
            'rental' => [
                'id' => $this->rental?->id,
                'reference' => $this->rental?->reference_number ?? null,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

