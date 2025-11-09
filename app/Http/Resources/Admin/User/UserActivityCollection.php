<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * User Activity Collection
 */
class UserActivityCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     */
    public $collects = UserActivityResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
            ],
        ];
    }

    /**
     * Additional data that should be returned with the resource array.
     */
    public function with($request): array
    {
        return [
            'success' => true,
            'message' => 'Activity log retrieved successfully.',
        ];
    }
}

