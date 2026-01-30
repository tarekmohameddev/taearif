<?php

namespace App\Http\Resources\Api\SupportCenter;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportCenterCategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'icon_image' => $this->icon_image ? asset($this->icon_image) : null,
            'articles_count' => $this->published_articles_count ?? 0,
        ];
    }
}
