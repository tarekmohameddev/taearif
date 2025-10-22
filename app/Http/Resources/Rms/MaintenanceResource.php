<?php

namespace App\Http\Resources\Rms;

use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceResource extends JsonResource
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
            'rental_id' => $this->rental_id,
            'category' => $this->category,
            'priority' => $this->priority,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'estimated_cost' => $this->estimated_cost ? (float) $this->estimated_cost : null,
            'actual_cost' => $this->actual_cost ? (float) $this->actual_cost : null,
            'payer' => $this->payer,
            'payer_share_percent' => $this->payer_share_percent,
            'scheduled_date' => $this->scheduled_date,
            'completed_date' => $this->completed_date,
            'assigned_to_vendor_id' => $this->assigned_to_vendor_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Optional: Include rental data if loaded
            'rental' => $this->whenLoaded('rental', function () {
                return [
                    'id' => $this->rental->id,
                    'tenant_name' => $this->rental->tenant_full_name,
                    'property_name' => $this->rental->property_name,
                ];
            }),
        ];
    }
}

