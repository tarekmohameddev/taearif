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

        try {
            if ($this->relationLoaded('categories')) {
                if ($this->categories && $this->categories->isNotEmpty()) {
                    $categories = CategoryResource::collection($this->categories)->resolve();
                }
            } else {
                // If not loaded, try to load them
                $this->loadMissing('categories');
                if ($this->categories && $this->categories->isNotEmpty()) {
                    $categories = CategoryResource::collection($this->categories)->resolve();
                }
            }
        } catch (\Exception $e) {
            // If any error occurs, ensure categories is still an empty array
            $categories = [];
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
