<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingOverviewResource extends JsonResource
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
            'whatsapp' => [
                'total_templates' => $this->resource['whatsapp']['total_templates'],
                'active_templates' => $this->resource['whatsapp']['active_templates'],
                'inactive_templates' => $this->resource['whatsapp']['inactive_templates'],
            ],
            'recent_templates' => WhatsAppTemplateResource::collection($this->resource['recent_templates']),
        ];
    }
}

