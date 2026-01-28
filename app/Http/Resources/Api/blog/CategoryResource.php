<?php

namespace App\Http\Resources\Api\blog;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'posts' => $this->when(
                $this->relationLoaded('posts'),
                PostListResource::collection($this->posts)
            ),
            'posts_count' => $this->relationLoaded('posts')
                ? $this->posts->count()
                : ($this->posts_count ?? 0),
        ];
    }
}
