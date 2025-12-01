<?php

namespace App\Http\Resources\Rms;

use App\Http\Resources\Rms\Concerns\ResolvesLocalizedNames;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    use ResolvesLocalizedNames;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $rental = $this->relationLoaded('rental') ? $this->rental : null;
        $propertyModel = $rental && $rental->relationLoaded('property') ? $rental->property : null;
        $projectModel = $rental && $rental->relationLoaded('project') ? $rental->project : null;
        $ownerId = $this->user_id ?? $rental?->user_id;

        $propertyId = $this->property_id ?? $rental?->unit_id;
        $projectId = $this->project_id ?? $rental?->project_id;

        $propertyName = $this->property_name
            ?? $this->resolvePropertyName($propertyModel, $propertyId, $ownerId);
        $projectName = $this->project_name
            ?? $this->resolveProjectName($projectModel, $projectId, $ownerId);

        return [
            'id' => $this->id,
            'rental_id' => $this->rental_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'property_id' => $propertyId,
            'project_id' => $projectId,
            'property_name' => $propertyName,
            'project_name' => $projectName,
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

