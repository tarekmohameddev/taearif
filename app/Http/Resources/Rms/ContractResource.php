<?php

namespace App\Http\Resources\Rms;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
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
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'property_id' => $this->property_id,
            'project_id' => $this->project_id,
            'property_name' => $this->property_name,
            'project_name' => $this->project_name,
            'grace_period_months' => $this->grace_period_months,
            'file_path' => $this->file_path,
            'termination_reason' => $this->termination_reason,
            'terminated_on' => $this->terminated_on,

            // Include contract number from rental if available
            'contract_number' => $this->rental?->contract_number ?? $this->contract_number,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Optional: Include rental data if loaded
            'rental' => $this->whenLoaded('rental', function () {
                return [
                    'id' => $this->rental->id,
                    'tenant_name' => $this->rental->tenant_full_name,
                    'contract_number' => $this->rental->contract_number,
                ];
            }),
        ];
    }
}

