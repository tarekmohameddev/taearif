<?php

namespace App\Http\Resources\Api\blog;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $name = null;
        if ($this->relationLoaded('user') && $this->user) {
            $name = trim(($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? '')) ?: ($this->user->username ?? $this->user->email);
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'thumbnail' => $this->when(
                $this->relationLoaded('thumbnail') && $this->thumbnail,
                new MediaResource($this->thumbnail)
            ),
            'status' => $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'author' => $this->when($this->relationLoaded('user') && $this->user, [
                'id' => $this->user->id,
                'name' => $name,
            ]),
        ];
    }
}
