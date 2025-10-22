<?php

namespace App\Http\Resources\Rms;

use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentResource extends JsonResource
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
            'contract_id' => $this->contract_id,
            'installment_number' => $this->installment_number,
            'due_date' => $this->due_date,
            'amount' => (float) $this->amount,
            'paid_amount' => (float) ($this->paid_amount ?? 0),
            'remaining_amount' => (float) ($this->amount - ($this->paid_amount ?? 0)),
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'is_overdue' => $this->is_overdue ?? false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

