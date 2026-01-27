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
        // Always try to get categories, even if not eager loaded
        $categories = [];
        if ($this->relationLoaded('categories')) {
            $categories = $this->categories ? CategoryResource::collection($this->categories)->resolve() : [];
        } else {
            // If not loaded, try to load them
            try {
                $this->loadMissing('categories');
                $categories = $this->categories ? CategoryResource::collection($this->categories)->resolve() : [];
            } catch (\Exception $e) {
                $categories = [];
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'thumbnail' => ($this->relationLoaded('thumbnail') && $this->thumbnail)
                ? new MediaResource($this->thumbnail)
                : null,
            'categories' => $categories,
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
