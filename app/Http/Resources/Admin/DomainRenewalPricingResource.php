<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class DomainRenewalPricingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'custom_domain_id' => $this->custom_domain_id,
            'domain' => $this->whenLoaded('customDomain', function () {
                if (!$this->customDomain) {
                    return null;
                }
                return [
                    'id' => $this->customDomain->id,
                    'user_id' => $this->customDomain->user_id,
                    'current_domain' => $this->customDomain->current_domain,
                    'requested_domain' => $this->customDomain->requested_domain,
                    'status' => $this->customDomain->status,
                ];
            }),
            'registrar' => $this->registrar,
            'period_key' => $this->period_key,
            'label' => $this->label,
            'years' => $this->years,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'active' => (bool) $this->active,
            'starts_at' => optional($this->starts_at)->toIso8601String(),
            'ends_at' => optional($this->ends_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
