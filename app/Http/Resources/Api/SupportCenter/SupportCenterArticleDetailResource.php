<?php

namespace App\Http\Resources\Api\SupportCenter;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportCenterArticleDetailResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'main_image' => $this->main_image ? asset($this->main_image) : null,
            'published_at' => $this->published_at?->toISOString(),
            'category' => $this->when(
                $this->relationLoaded('category') && $this->category,
                [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ]
            ),
            'cta_enabled' => $this->cta_enabled,
        ];
        if ($this->cta_enabled) {
            $data['cta'] = [
                'text' => $this->cta_text,
                'url' => $this->cta_url,
                'target_blank' => $this->cta_target_blank,
            ];
        }
        return $data;
    }
}
