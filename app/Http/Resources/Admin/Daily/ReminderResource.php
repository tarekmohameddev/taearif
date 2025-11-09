<?php

namespace App\Http\Resources\Admin\Daily;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reminder Resource
 *
 * Transforms CRM Reminder model for API responses
 */
class ReminderResource extends JsonResource
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
            'title' => $this->title,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'datetime' => $this->datetime?->format('Y-m-d H:i:s'),
            'date' => $this->datetime?->format('Y-m-d'),
            'time' => $this->datetime?->format('H:i'),
            'is_overdue' => $this->datetime ? $this->datetime->isPast() : false,
            'user' => [
                'id' => $this->user?->id,
                'uuid' => $this->user?->uuid,
                'username' => $this->user?->username,
                'email' => $this->user?->email,
                'full_name' => $this->user ? trim($this->user->first_name . ' ' . $this->user->last_name) : null,
            ],
            'customer' => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
                'phone' => $this->customer?->phone,
                'email' => $this->customer?->email,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

