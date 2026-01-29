<?php

namespace App\Http\Resources\Api\Articles;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleListResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
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
        ];
    }
}
