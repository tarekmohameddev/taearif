<?php

namespace App\Http\Resources\Rms;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'expense_name' => $this->expense_name,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'amount_type' => $this->amount_type,
            'amount_value' => (float) $this->amount_value,
            'calculated_amount' => $this->calculated_amount, // Uses model accessor!
            'cost_center' => $this->cost_center,
            'is_active' => $this->is_active,
            'can_be_modified' => $this->canBeModified(),
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

