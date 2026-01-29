<?php

namespace App\Http\Resources\Api\Articles;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $adminName = null;
        if ($this->relationLoaded('admin') && $this->admin) {
            $adminName = trim(($this->admin->first_name ?? '') . ' ' . ($this->admin->last_name ?? '')) ?: ($this->admin->username ?? $this->admin->email);
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'main_image' => $this->main_image ? asset($this->main_image) : null,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'category' => $this->when(
                $this->relationLoaded('category') && $this->category,
                [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ]
            ),
            'author' => $this->when($this->relationLoaded('admin') && $this->admin, [
                'id' => $this->admin->id,
                'name' => $adminName,
            ]),
            'meta' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
                'og_image' => $this->og_image ? asset($this->og_image) : null,
            ],
            'cta' => $this->when($this->cta_enabled, [
                'enabled' => $this->cta_enabled,
                'text' => $this->cta_text,
                'url' => $this->cta_url,
                'target_blank' => $this->cta_target_blank,
            ]),
        ];
    }
}
