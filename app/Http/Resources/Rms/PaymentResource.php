<?php

namespace App\Http\Resources\Rms;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'installment_id' => $this->installment_id,
            'cost_item_id' => $this->cost_item_id,
            'payment_type' => $this->payment_type,
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date,
            'reference' => $this->reference,
            'bank_name' => $this->bank_name,
            'transfer_to' => $this->transfer_to,
            'notes' => $this->notes,

            // Receipt image with auto-generated URL
            'receipt_image_path' => $this->receipt_image_path,
            'receipt_image_url' => $this->receipt_image_path
                ? url($this->receipt_image_path)
                : null,

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Optional: Include installment data if loaded
            'installment' => $this->whenLoaded('installment', function () {
                return [
                    'id' => $this->installment->id,
                    'installment_number' => $this->installment->installment_number,
                    'due_date' => $this->installment->due_date,
                    'amount' => (float) $this->installment->amount,
                ];
            }),
        ];
    }
}

