<?php

namespace App\Http\Resources\Crm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReminderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'reminder_type_id' => $this->reminder_type_id,
            'reminder_type' => $this->whenLoaded('reminderType', function () {
                return [
                    'id' => $this->reminderType->id,
                    'user_id' => $this->reminderType->user_id,
                    'name' => $this->reminderType->name,
                    'name_ar' => $this->reminderType->name_ar,
                    'description' => $this->reminderType->description,
                    'color' => $this->reminderType->color,
                    'icon' => $this->reminderType->icon,
                    'order' => $this->reminderType->order,
                    'is_active' => $this->reminderType->is_active,
                    'created_at' => $this->reminderType->created_at?->toIso8601String(),
                    'updated_at' => $this->reminderType->updated_at?->toIso8601String(),
                ];
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'name_ar' => $this->customer->name_ar ?? null,
                    'phone_number' => $this->customer->phone_number,
                    'email' => $this->customer->email,
                    'city' => $this->customer->city?->name ?? null,
                    'district' => $this->customer->district?->name ?? null,
                    'created_at' => $this->customer->created_at?->toIso8601String(),
                    'updated_at' => $this->customer->updated_at?->toIso8601String(),
                ];
            }),
            'title' => $this->title,
            'description' => $this->description,
            'datetime' => $this->datetime?->format('Y-m-d H:i:s'),
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'priority_label_ar' => $this->priority_label_ar,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_label_ar' => $this->status_label_ar,
            'notes' => $this->notes,
            'is_overdue' => $this->is_overdue,
            'days_until_due' => $this->days_until_due,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
