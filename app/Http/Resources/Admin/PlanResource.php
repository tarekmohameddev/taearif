<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Plan Resource
 *
 * Transforms Plan model for API responses
 */
class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'term' => $this->term,
            'icon' => $this->icon ? url($this->icon) : null,
            'status' => [
                'is_active' => $this->is_active,
                'status_code' => $this->status,
                'featured' => $this->featured === 1,
            ],
            'trial' => [
                'enabled' => $this->is_trial,
                'days' => $this->trial_days,
            ],
            'limits' => [
                'vcards' => $this->number_of_vcards,
                'projects' => $this->project_limit_number,
                'real_estate' => $this->real_estate_limit_number,
                'video_size' => $this->video_size_limit,
                'file_size' => $this->file_size_limit,
            ],
            'features' => $this->features,
            'new_features' => $this->new_features,
            'seo' => [
                'meta_keywords' => $this->meta_keywords,
                'meta_description' => $this->meta_description,
            ],
            'serial_number' => $this->serial_number,
            'subscribers_count' => $this->subscribers_count ?? 0,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

