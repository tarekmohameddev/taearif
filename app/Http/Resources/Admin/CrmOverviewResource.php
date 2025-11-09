<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class CrmOverviewResource extends JsonResource
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
            'statistics' => [
                'total_leads' => $this->resource['total_leads'],
                'active_leads' => $this->resource['active_leads'],
                'converted_leads' => $this->resource['converted_leads'],
                'lost_leads' => $this->resource['lost_leads'],
            ],
            'leads_by_status' => $this->resource['leads_by_status'],
            'leads_by_source' => $this->resource['leads_by_source'],
            'recent_leads' => LeadResource::collection($this->resource['recent_leads']),
        ];
    }
}

