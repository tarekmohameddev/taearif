<?php

namespace App\Http\Resources\Rms;

use Illuminate\Http\Resources\Json\JsonResource;

class RentalResource extends JsonResource
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
            'user_id' => $this->user_id,

            // Tenant Information
            'tenant_full_name' => $this->tenant_full_name,
            'tenant_phone' => $this->tenant_phone,
            'tenant_email' => $this->tenant_email,
            'tenant_job_title' => $this->tenant_job_title,
            'tenant_social_status' => $this->tenant_social_status,
            'tenant_national_id' => $this->tenant_national_id,

            // Property Information
            'unit_id' => $this->unit_id,
            'project_id' => $this->project_id,
            'building_id' => $this->building_id,
            'property_id' => $this->property_id,
            'property_name' => $this->property_name,
            'project_name' => $this->project_name,
            'building_name' => $this->building_name,

            // Rental Details
            'move_in_date' => $this->move_in_date,
            'rental_type' => $this->rental_type,
            'rental_duration' => $this->rental_duration,
            'paying_plan' => $this->paying_plan,
            'total_rental_amount' => $this->total_rental_amount ? (float) $this->total_rental_amount : null,
            'base_rent_amount' => $this->base_rent_amount ? (float) $this->base_rent_amount : null,
            'currency' => $this->currency,
            'contract_number' => $this->contract_number,
            'notes' => $this->notes,
            'status' => $this->status,
            'end_date' => $this->end_date,
            'termination_reason' => $this->termination_reason,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Optional: Include active contract if loaded
            'active_contract' => ContractResource::make($this->whenLoaded('activeContract')),

            // Optional: Include cost items if loaded
            'tenant_cost_items' => $this->when(
                $this->relationLoaded('tenantCostItems'),
                fn() => $this->tenantCostItems
            ),

            'owner_cost_items' => $this->when(
                $this->relationLoaded('ownerCostItems'),
                fn() => $this->ownerCostItems
            ),

            // Optional: Include property if loaded
            'property' => $this->whenLoaded('property', function () {
                return [
                    'id' => $this->property->id,
                    'property_name' => $this->property->property_name,
                    'property_type' => $this->property->property_type,
                ];
            }),
        ];
    }
}

