<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Theme Resource
 *
 * Transforms ApiThemeSettings model for API responses
 */
class ThemeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->theme_id,
            'theme_id' => $this->theme_id,
            'name' => $this->name,
            'description' => $this->description,
            'thumbnail' => asset($this->thumbnail),
            'category' => $this->category,
            'is_free' => (bool) $this->is_free,
            'is_enabled' => (bool) $this->is_enabled,
            'price' => $this->price ? (float) $this->price : null,
            'currency' => $this->currency,
            'popular' => (bool) $this->popular,
            'active' => (bool) $this->active,
            'purchases_count' => $this->whenLoaded('userThemes', function () {
                return $this->userThemes->count();
            }, function () {
                return $this->userThemes()->count();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
