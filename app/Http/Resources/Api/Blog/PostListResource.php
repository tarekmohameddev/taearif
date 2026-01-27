<?php

namespace App\Http\Resources\Api\blog;

use Illuminate\Http\Resources\Json\JsonResource;

class PostListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'thumbnail' => $this->when(
                $this->relationLoaded('thumbnail') && $this->thumbnail,
                new MediaResource($this->thumbnail)
            ),
            'categories' => $this->when(
                $this->relationLoaded('categories'),
                CategoryResource::collection($this->categories)
            ),
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
